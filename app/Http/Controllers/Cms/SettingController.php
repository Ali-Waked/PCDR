<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Services\AuditService;
use App\Services\CmsAccess;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class SettingController extends Controller
{
    /** @var array<string, array{group: string, localized: bool, type: string}> */
    private const PUBLIC_SETTINGS = [
        'organization.name' => ['group' => 'organization', 'localized' => true, 'type' => 'text'],
        'organization.short_name' => ['group' => 'organization', 'localized' => true, 'type' => 'text'],
        'organization.logo' => ['group' => 'organization', 'localized' => false, 'type' => 'url'],
        'website.default_language' => ['group' => 'website', 'localized' => false, 'type' => 'locale'],
        'seo.site_title' => ['group' => 'seo', 'localized' => true, 'type' => 'text'],
        'seo.default_description' => ['group' => 'seo', 'localized' => true, 'type' => 'textarea'],
        'seo.og_image' => ['group' => 'seo', 'localized' => false, 'type' => 'url'],
        'footer.copyright' => ['group' => 'footer', 'localized' => true, 'type' => 'text'],
        'footer.legal_note' => ['group' => 'footer', 'localized' => true, 'type' => 'textarea'],
        'contact.email' => ['group' => 'contact', 'localized' => false, 'type' => 'email'],
        'contact.phone' => ['group' => 'contact', 'localized' => false, 'type' => 'text'],
        'contact.address' => ['group' => 'contact', 'localized' => false, 'type' => 'text'],
        'contact.hours' => ['group' => 'contact', 'localized' => false, 'type' => 'text'],
        'social.facebook' => ['group' => 'social', 'localized' => false, 'type' => 'url'],
        'social.x' => ['group' => 'social', 'localized' => false, 'type' => 'url'],
        'social.instagram' => ['group' => 'social', 'localized' => false, 'type' => 'url'],
        'social.linkedin' => ['group' => 'social', 'localized' => false, 'type' => 'url'],
        'social.youtube' => ['group' => 'social', 'localized' => false, 'type' => 'url'],
    ];

    public function __construct(private AuditService $audit) {}

    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale): Response
    {
        $this->authorize('settings.view');

        $settings = WebsiteSetting::query()->with('translations')->get()->keyBy('key');
        $locales = (array) config('website.locales', ['ar', 'en']);

        return Inertia::render('Cms/Settings/Index', [
            'settings' => collect(self::PUBLIC_SETTINGS)->map(function (array $meta, string $key) use ($settings, $locales): array {
                $setting = $settings->get($key);
                $values = [];

                if ($meta['localized']) {
                    foreach ($locales as $code) {
                        $values[$code] = $setting?->translations->firstWhere('locale', $code)?->value;
                    }
                }

                return [
                    'key' => $key,
                    'group' => $meta['group'],
                    'type' => $meta['type'],
                    'localized' => $meta['localized'],
                    'label' => __('website.cms.setting_labels'.'.'.Str::replace(['.'], '_', $key)),
                    'value' => $setting?->value,
                    'translations' => $values,
                ];
            })->values()->all(),
            'locales' => $locales,
        ]);
    }

    public function update(string $locale, Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $validated = $request->validate([
            'settings' => ['required', 'array', 'max:'.count(self::PUBLIC_SETTINGS)],
            'settings.*.key' => ['required', 'string', 'in:'.implode(',', array_keys(self::PUBLIC_SETTINGS))],
            'settings.*.value' => ['nullable', 'string', 'max:1000'],
            'settings.*.translations' => ['nullable', 'array'],
            'settings.*.translations.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = [];
        $after = [];

        DB::transaction(function () use ($validated, &$before, &$after): void {
            foreach ($validated['settings'] as $entry) {
                $key = $entry['key'];
                $meta = self::PUBLIC_SETTINGS[$key];
                $setting = WebsiteSetting::query()->firstOrNew(['key' => $key]);
                $before[$key] = $setting->exists ? $setting->resolvedValue() : null;

                $attributes = [
                    'group' => $meta['group'],
                    'type' => $meta['type'],
                    'is_public' => true,
                    'is_localized' => $meta['localized'],
                ];

                if ($meta['localized']) {
                    $attributes['value'] = null;
                    $setting->fill($attributes)->save();

                    foreach ((array) ($entry['translations'] ?? []) as $code => $value) {
                        if (! in_array($code, (array) config('website.locales', ['ar', 'en']), true)) {
                            continue;
                        }

                        $this->validateValue($meta['type'], $value);
                        $setting->translations()->updateOrCreate(
                            ['locale' => $code],
                            ['value' => $value === '' ? null : $value],
                        );
                    }
                } else {
                    $value = $entry['value'] ?? null;
                    $this->validateValue($meta['type'], $value);
                    $attributes['value'] = $value === '' ? null : $value;
                    $setting->fill($attributes)->save();
                }

                $after[$key] = $setting->fresh()?->resolvedValue();
            }
        });

        app(SiteSettings::class)->flush();
        $this->audit->record('cms.settings.update', 'website_settings', null, $before, $after, [
            'keys' => array_column($validated['settings'], 'key'),
        ]);

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    private function validateValue(string $type, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $rules = match ($type) {
            'email' => ['email:rfc'],
            'url' => ['url'],
            'locale' => ['in:'.implode(',', (array) config('website.locales', ['ar', 'en']))],
            default => [],
        };

        if ($rules !== []) {
            validator(['value' => $value], ['value' => $rules])->validate();
        }
    }
}
