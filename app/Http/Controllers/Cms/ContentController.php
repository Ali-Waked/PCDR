<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Actions\TransitionContentStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Media;
use App\Services\CmsAccess;
use App\Services\TranslationSaver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class ContentController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;

    protected string $resource;

    protected string $label;

    protected string $routeBase;

    protected string $foreignKey;

    /**
     * Column that stores the publication workflow state. Projects keep their
     * lifecycle in `status` and therefore override this to `workflow_status`.
     */
    protected string $statusColumn = 'status';

    /** @var list<string> */
    protected array $translationFields = [
        'title', 'slug', 'summary', 'body', 'seo_title', 'seo_description',
    ];

    /** @var array<string, mixed> */
    protected array $extraRules = [];

    /** @var list<string> */
    protected array $extraFillable = [];

    public function index(string $locale): Response
    {
        $this->authorizeCms($this->resource.'.view');

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $items = $class::query()->with('translations')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return Inertia::render('Cms/Content/Index', [
            'resource' => $this->resource,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
            'items' => $items->through(fn (Model $item) => [
                'id' => $item->getKey(),
                'title' => $item->t('title', $locale) ?: $item->t('title'),
                'status' => ContentStatus::valueOf($item->workflow_status ?? $item->status),
                'publishedAt' => $item->published_at?->toDateString(),
                'updatedAt' => $item->updated_at?->toDateString(),
                'locales' => $item->availableLocales(),
            ])->items(),
            'pagination' => [
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'total' => $items->total(),
            ],
            'counts' => $this->statusCounts($class),
        ]);
    }

    public function create(string $locale): Response
    {
        $this->authorizeCms($this->resource.'.view');

        return $this->editResponse($locale, null);
    }

    public function store(string $locale, Request $request): RedirectResponse
    {
        $this->authorizeCms($this->resource.'.update');
        $data = $this->validateContent($request);

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $record = new $class;
        $record->fill($this->attributes($data));
        $record->save();

        app(TranslationSaver::class)->sync($record, $data['translations'], $this->translationFields, $this->foreignKey);
        $this->afterSave($record, $data);
        $this->revision($record, $request, 'created');

        return to_route($this->routeBase.'.edit', ['locale' => $locale, 'content' => $record->getKey()])
            ->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function edit(string $locale, int $content): Response
    {
        $this->authorizeCms($this->resource.'.view');

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $record = $class::query()->with('translations')->findOrFail($content);

        return $this->editResponse($locale, $record);
    }

    public function update(string $locale, Request $request, int $content): RedirectResponse
    {
        $this->authorizeCms($this->resource.'.update');
        $data = $this->validateContent($request);

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $record = $class::query()->findOrFail($content);
        $attributes = $this->attributes($data);
        unset($attributes[$this->statusColumn]);
        $record->fill($attributes);
        $record->save();

        app(TranslationSaver::class)->sync($record, $data['translations'], $this->translationFields, $this->foreignKey);
        $this->afterSave($record, $data);
        $this->revision($record, $request, 'updated');

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function destroy(string $locale, int $content): RedirectResponse
    {
        $this->authorizeCms($this->resource.'.update');

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $record = $class::query()->findOrFail($content);
        $record->delete();

        return to_route($this->routeBase.'.index', ['locale' => $locale])
            ->with('flash', ['success' => __('website.cms.archived')]);
    }

    public function status(string $locale, Request $request, int $content): RedirectResponse
    {
        $this->authorizeCms($this->resource.'.update');
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', ContentStatus::values())],
        ]);

        /** @var class-string<Model> $class */
        $class = $this->modelClass;
        $record = $class::query()->findOrFail($content);
        app(TransitionContentStatus::class)->handle(
            $record,
            ContentStatus::from($validated['status']),
            $request->user(),
        );

        return back()->with('flash', ['success' => __('website.cms.status_updated')]);
    }

    /** @return array<string, mixed> */
    protected function validateContent(Request $request): array
    {
        return $request->validate(array_merge([
            'status' => ['required', 'in:'.implode(',', ContentStatus::values())],
            'published_at' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:180'],
            'translations.*.summary' => ['nullable', 'string', 'max:1000'],
            'translations.*.excerpt' => ['nullable', 'string', 'max:1000'],
            'translations.*.body' => ['nullable', 'string', 'max:100000'],
            'translations.*.description' => ['nullable', 'string', 'max:100000'],
            'translations.*.objectives' => ['nullable', 'string', 'max:20000'],
            'translations.*.target_groups' => ['nullable', 'string', 'max:20000'],
            'translations.*.geographic_coverage' => ['nullable', 'string', 'max:255'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:255'],
            'translations.*.seo_description' => ['nullable', 'string', 'max:500'],
            'translations.*.short_title' => ['nullable', 'string', 'max:120'],
        ], $this->extraRules));
    }

    /** @param class-string<Model> $class @return array<string, int> */
    private function statusCounts(string $class): array
    {
        return [
            'all' => $class::query()->count(),
            'published' => $class::query()->where($this->statusColumn, ContentStatus::Published->value)->count(),
            'draft' => $class::query()->where($this->statusColumn, ContentStatus::Draft->value)->count(),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function attributes(array $data): array
    {
        $attributes = [
            $this->statusColumn => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
        ];

        foreach ($this->extraFillable as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        return $attributes;
    }

    /** @param array<string, mixed> $data */
    protected function afterSave(Model $record, array $data): void
    {
        //
    }

    protected function revision(Model $record, Request $request, string $note): void
    {
        $payload = [];
        foreach ($record->translations()->get() as $translation) {
            $payload[$translation->locale] = ['title' => $translation->getAttribute('title')];
        }

        ContentRevision::create([
            'revisionable_type' => $record->getMorphClass(),
            'revisionable_id' => $record->getKey(),
            'locale' => app()->getLocale(),
            'title' => $record->t('title'),
            'status' => ContentStatus::valueOf($record->workflow_status ?? $record->status, ''),
            'payload' => $payload,
            'actor_id' => $request->user()?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    protected function authorizeCms(string $permission): void
    {
        abort_unless(
            auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission),
            403,
        );
    }

    private function editResponse(string $locale, ?Model $record): Response
    {
        $translations = [];
        foreach (config('website.locales', ['ar', 'en']) as $code) {
            $translation = $record?->translations->firstWhere('locale', $code);
            foreach ($this->translationFields as $field) {
                $translations[$code][$field] = $translation?->getAttribute($field) ?? '';
            }
        }

        return Inertia::render('Cms/Content/Edit', [
            'resource' => $this->resource,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
            'record' => $record ? [
                'id' => $record->getKey(),
                'status' => ContentStatus::valueOf($record->workflow_status ?? $record->status),
                'publishedAt' => $record->published_at?->toDateString(),
                'displayOrder' => $record->display_order,
                'attributes' => $record->only($this->extraFillable),
            ] : null,
            'translations' => $translations,
            'statuses' => collect(ContentStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label($locale),
            ])->all(),
            'revisions' => $record
                ? ContentRevision::query()
                    ->where('revisionable_type', $record->getMorphClass())
                    ->where('revisionable_id', $record->getKey())
                    ->latest('created_at')
                    ->limit(10)
                    ->get(['id', 'locale', 'status', 'note', 'created_at'])
                    ->all()
                : [],
            'options' => array_merge($this->options(), ['media' => $this->mediaOptions()]),
        ]);
    }

    /** @return list<array{id: int, name: string, url: string, alt: string, type: string}> */
    private function mediaOptions(): array
    {
        if (! auth()->check() || ! app(CmsAccess::class)->allows(auth()->user(), 'media.view')) {
            return [];
        }

        return Media::query()
            ->images()
            ->where('is_restricted', false)
            ->with('translations')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => $media->original_name,
                'url' => $media->url(),
                'alt' => $media->t('alt_text', app()->getLocale()),
                'type' => $media->type->value,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    protected function options(): array
    {
        return [];
    }
}
