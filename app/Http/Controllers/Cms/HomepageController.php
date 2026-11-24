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
use Illuminate\Validation\ValidationException;
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

        $sections = HomepageSection::query()->with('translations', 'items.translations')->orderBy('display_order')->get();

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
            $sections = HomepageSection::query()->with('translations', 'items.translations')->orderBy('display_order')->get();
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
                    'itemKey' => $item->item_key,
                    'isPublished' => $item->is_published,
                    'numericValue' => $item->numeric_value,
                    'sortOrder' => $item->sort_order,
                    'translations' => $item->translations->mapWithKeys(fn ($t) => [$t->locale => [
                        'label' => $t->label,
                        'title' => $t->title,
                        'body' => $t->body,
                        'display_value' => $t->display_value,
                        'url' => $t->url,
                    ]])->all(),
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
            'sections.*.items' => ['nullable', 'array', 'max:24'],
            'sections.*.items.*.id' => ['nullable', 'integer'],
            'sections.*.items.*.item_type' => ['required', 'in:pillar,article,publication,page,project,inline'],
            'sections.*.items.*.item_id' => ['nullable', 'integer'],
            'sections.*.items.*.item_key' => ['nullable', 'string', 'max:64'],
            'sections.*.items.*.is_published' => ['boolean'],
            'sections.*.items.*.numeric_value' => ['nullable', 'numeric'],
            'sections.*.items.*.translations' => ['nullable', 'array'],
            'sections.*.items.*.translations.*.label' => ['nullable', 'string', 'max:120'],
            'sections.*.items.*.translations.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.items.*.translations.*.body' => ['nullable', 'string', 'max:5000'],
            'sections.*.items.*.translations.*.display_value' => ['nullable', 'string', 'max:64'],
            'sections.*.items.*.translations.*.url' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateItems($validated);

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

                if (! array_key_exists('items', $sectionData)) {
                    continue;
                }

                $incoming = collect($sectionData['items']);
                $keepIds = $incoming->pluck('id')->filter()->all();
                $section->items()->whereNotIn('id', $keepIds)->delete();

                foreach ($incoming as $index => $itemData) {
                    $attributes = [
                        'item_type' => $itemData['item_type'],
                        'item_id' => $itemData['item_id'] ?? null,
                        'item_key' => $itemData['item_key'] ?? null,
                        'is_published' => $itemData['is_published'] ?? true,
                        'numeric_value' => $itemData['numeric_value'] ?? null,
                        'sort_order' => $index,
                    ];

                    $item = ! empty($itemData['id'])
                        ? $section->items()->find($itemData['id'])
                        : null;

                    if ($item) {
                        $item->update($attributes);
                    } else {
                        $item = $section->items()->create($attributes);
                    }

                    foreach ($itemData['translations'] ?? [] as $code => $values) {
                        if (! in_array($code, config('website.locales', ['ar', 'en']), true)) {
                            continue;
                        }
                        $item->translations()->updateOrCreate(['locale' => $code], $values);
                    }
                }
            }
        });

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateItems(array $validated): void
    {
        foreach ($validated['sections'] as $sectionIndex => $sectionData) {
            $items = $sectionData['items'] ?? [];
            $keys = [];

            foreach ($items as $itemIndex => $item) {
                $path = "sections.{$sectionIndex}.items.{$itemIndex}";

                if ($item['item_type'] !== 'inline' && empty($item['item_id'])) {
                    throw ValidationException::withMessages([
                        $path.'.item_id' => 'An item ID is required for this item type.',
                    ]);
                }

                if (! empty($item['item_key'])) {
                    if (in_array($item['item_key'], $keys, true)) {
                        throw ValidationException::withMessages([
                            $path.'.item_key' => 'Item keys must be unique within a section.',
                        ]);
                    }
                    $keys[] = $item['item_key'];
                }
            }
        }
    }
}
