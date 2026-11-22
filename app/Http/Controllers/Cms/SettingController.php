<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
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
    private const KEYS = [
        'contact.email' => 'Contact email (verified)',
        'contact.phone' => 'Contact phone (verified)',
        'contact.address' => 'Contact address (verified)',
        'contact.hours' => 'Office hours (verified)',
        'social.facebook' => 'Facebook URL (verified)',
        'social.x' => 'X URL (verified)',
        'social.instagram' => 'Instagram URL (verified)',
        'social.linkedin' => 'LinkedIn URL (verified)',
        'social.youtube' => 'YouTube URL (verified)',
    ];

    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale): Response
    {
        $this->authorize('settings.view');

        return Inertia::render('Cms/Settings/Index', [
            'settings' => collect(self::KEYS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'value' => WebsiteSetting::query()->where('key', $key)->first()?->value,
            ])->values()->all(),
        ]);
    }

    public function update(string $locale, Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'in:'.implode(',', array_keys(self::KEYS))],
            'settings.*.value' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['settings'] as $entry) {
                $key = $entry['key'];
                $group = Str::before($key, '.');
                WebsiteSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'group' => $group,
                        'type' => 'string',
                        'is_public' => true,
                        'is_localized' => false,
                        'value' => $entry['value'] ?: null,
                    ],
                );
            }
        });

        app(SiteSettings::class)->flush();

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }
}
