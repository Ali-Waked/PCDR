<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class SettingController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Setting::class);

        $definitions = collect($this->settings->definitions())
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                'group' => $definition['group'],
                'type' => $definition['type'],
                'public' => (bool) ($definition['public'] ?? false),
                'label' => $definition['label'] ?? $key,
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/Settings/Index', [
            'definitions' => $definitions,
            'values' => $this->settings->publicSettings() +
                collect($this->settings->definitions())
                    ->reject(fn (array $definition) => (bool) ($definition['public'] ?? false))
                    ->mapWithKeys(fn (array $definition, string $key) => [
                        $key => $this->settings->get($key),
                    ])
                    ->all(),
        ]);
    }

    public function update(string $locale, UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->settingsValues($this->settings));

        return back()->with('success', __('ui.settings_updated'));
    }
}
