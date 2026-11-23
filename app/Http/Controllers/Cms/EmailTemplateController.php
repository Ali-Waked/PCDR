<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\AuditService;
use App\Services\CmsAccess;
use App\Services\SiteSettings;
use App\Services\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class EmailTemplateController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private TemplateRenderer $renderer,
    ) {}

    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale): Response
    {
        $this->authorize('email_templates.view');

        $templates = EmailTemplate::query()->with('translations')->orderBy('sort_order')->get();

        return Inertia::render('Cms/EmailTemplates/Index', [
            'templates' => $templates->map(fn (EmailTemplate $template) => [
                'id' => $template->id,
                'key' => $template->key,
                'active' => $template->is_active,
                'sortOrder' => $template->sort_order,
                'translations' => $template->translations->mapWithKeys(fn ($translation) => [
                    $translation->locale => [
                        'name' => $translation->name,
                        'description' => $translation->description,
                        'subject' => $translation->subject,
                        'body' => $translation->body,
                    ],
                ])->all(),
            ])->all(),
            'locales' => config('website.locales', ['ar', 'en']),
            'variables' => $this->renderer->availableVariables(),
        ]);
    }

    public function store(string $locale, Request $request): RedirectResponse
    {
        $this->authorize('email_templates.create');

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:96', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:email_templates,key'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
            'translations.*.subject' => ['required', 'string', 'max:255'],
            'translations.*.body' => ['required', 'string', 'max:10000'],
        ]);

        $template = DB::transaction(function () use ($validated): EmailTemplate {
            $template = EmailTemplate::create([
                'key' => $validated['key'],
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);
            $this->syncTranslations($template, $validated['translations']);

            return $template;
        });

        $this->audit->record('email_templates.create', 'email_templates', (string) $template->id, extra: [
            'key' => $template->key,
        ]);

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function update(string $locale, EmailTemplate $template, Request $request): RedirectResponse
    {
        $this->authorize('email_templates.update');

        $validated = $request->validate([
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
            'translations.*.subject' => ['required', 'string', 'max:255'],
            'translations.*.body' => ['required', 'string', 'max:10000'],
        ]);
        $before = $this->snapshot($template);

        DB::transaction(function () use ($template, $validated): void {
            $template->update([
                'is_active' => $validated['is_active'] ?? false,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);
            $this->syncTranslations($template, $validated['translations']);
        });

        $this->audit->record('email_templates.update', 'email_templates', (string) $template->id, $before, $this->snapshot($template->fresh()));

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function destroy(string $locale, EmailTemplate $template): RedirectResponse
    {
        $this->authorize('email_templates.delete');

        $key = $template->key;
        $template->delete();
        $this->audit->record('email_templates.delete', 'email_templates', (string) $template->id, extra: ['key' => $key]);

        return back()->with('flash', ['success' => __('website.cms.deleted')]);
    }

    public function preview(string $locale, Request $request): JsonResponse
    {
        $this->authorize('email_templates.view');

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
        $variables = [
            'sender_name' => __('website.cms.preview_sender_name'),
            'sender_email' => 'sender@example.com',
            'inquiry_subject' => __('website.cms.preview_inquiry_subject'),
            'inquiry_reference' => '01JPREVIEW00000000000000000',
            'reply_message' => __('website.cms.preview_reply_message'),
            'organization_name' => app(SiteSettings::class)->value('organization.name', config('app.name'), $locale),
            'current_date' => now()->locale($locale)->translatedFormat('j F Y'),
        ];

        return response()->json([
            'subject' => $this->renderer->render($validated['subject'], $variables, false),
            'body' => $this->renderer->render($validated['body'], $variables, false),
        ]);
    }

    /** @param array<string, array<string, mixed>> $translations */
    private function syncTranslations(EmailTemplate $template, array $translations): void
    {
        foreach ($translations as $code => $values) {
            if (! in_array($code, config('website.locales', ['ar', 'en']), true)) {
                continue;
            }

            $template->translations()->updateOrCreate(['locale' => $code], [
                'name' => $values['name'],
                'description' => $values['description'] ?? null,
                'subject' => $values['subject'],
                'body' => $values['body'],
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(EmailTemplate $template): array
    {
        return [
            'key' => $template->key,
            'is_active' => $template->is_active,
            'sort_order' => $template->sort_order,
            'translations' => $template->translations->map(fn ($translation) => [
                'locale' => $translation->locale,
                'name' => $translation->name,
                'subject' => $translation->subject,
                'body' => $translation->body,
            ])->all(),
        ];
    }
}
