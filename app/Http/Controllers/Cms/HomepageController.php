<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\CmsAccess;
use App\Services\HomepageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HomepageController extends Controller
{
    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale): Response
    {
        $this->authorize('homepage.view');

        $sections = HomepageSection::query()->with('translations', 'items')->orderBy('display_order')->get();

        if ($sections->isEmpty()) {
            foreach (['intro', 'areas', 'framework', 'programs', 'news', 'publications',
                'transparency', 'partnerships', 'participation', 'cta'] as $index => $key) {
                HomepageSection::create([
                    'key' => $key,
                    'type' => $key,
                    'is_enabled' => true,
                    'display_order' => $index,
                ]);
            }
            $sections = HomepageSection::query()->with('translations', 'items')->orderBy('display_order')->get();
        }

        return Inertia::render('Cms/Homepage/Index', [
            'sections' => $sections->map(fn (HomepageSection $section) => [
                'id' => $section->id,
                'key' => $section->key,
                'type' => $section->type,
                'enabled' => $section->is_enabled,
                'order' => $section->display_order,
                'translations' => $section->translations->mapWithKeys(fn ($t) => [$t->locale => [
                    'eyebrow' => $t->eyebrow,
                    'title' => $t->title,
                    'body' => $t->body,
                    'cta_label' => $t->cta_label,
                    'cta_link' => $t->cta_link,
                ]])->all(),
                'items' => $section->items->map(fn ($item) => [
                    'id' => $item->id,
                    'itemType' => $item->item_type,
                    'itemId' => $item->item_id,
                    'sortOrder' => $item->sort_order,
                ])->all(),
            ])->all(),
            'preview' => app(HomepageService::class)->sections($locale),
        ]);
    }

    public function update(string $locale, Request $request): RedirectResponse
    {
        $this->authorize('homepage.update');

        $validated = $request->validate([
            'sections' => ['required', 'array', 'max:30'],
            'sections.*.id' => ['required', 'integer', 'exists:homepage_sections,id'],
            'sections.*.is_enabled' => ['boolean'],
            'sections.*.display_order' => ['integer', 'min:0', 'max:999'],
            'sections.*.translations' => ['nullable', 'array'],
            'sections.*.translations.*.eyebrow' => ['nullable', 'string', 'max:120'],
            'sections.*.translations.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.translations.*.body' => ['nullable', 'string', 'max:5000'],
            'sections.*.translations.*.cta_label' => ['nullable', 'string', 'max:120'],
            'sections.*.translations.*.cta_link' => ['nullable', 'string', 'max:255'],
            'sections.*.items' => ['nullable', 'array', 'max:12'],
            'sections.*.items.*.item_type' => ['required', 'in:pillar,article,publication,page,project'],
            'sections.*.items.*.item_id' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['sections'] as $sectionData) {
                $section = HomepageSection::query()->findOrFail($sectionData['id']);
                $section->update([
                    'is_enabled' => $sectionData['is_enabled'] ?? false,
                    'display_order' => $sectionData['display_order'] ?? 0,
                ]);

                foreach ($sectionData['translations'] ?? [] as $code => $values) {
                    if (! in_array($code, config('website.locales', ['ar', 'en']), true)) {
                        continue;
                    }
                    $section->translations()->updateOrCreate(['locale' => $code], $values);
                }

                if (array_key_exists('items', $sectionData)) {
                    $items = collect($sectionData['items'])
                        ->map(fn ($item, $index) => [
                            'item_type' => $item['item_type'],
                            'item_id' => $item['item_id'],
                            'sort_order' => $index,
                        ])->all();

                    $section->items()->delete();
                    foreach ($items as $item) {
                        $section->items()->create($item);
                    }
                }
            }
        });

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }
}
