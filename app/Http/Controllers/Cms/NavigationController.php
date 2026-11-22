<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Services\CmsAccess;
use App\Services\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class NavigationController extends Controller
{
    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale): Response
    {
        $this->authorize('navigation.view');

        return Inertia::render('Cms/Navigation/Index', [
            'menus' => NavigationMenu::query()->with('items.translations')->get()->map(fn (NavigationMenu $menu) => [
                'id' => $menu->id,
                'key' => $menu->key,
                'label' => $menu->label,
                'items' => $menu->items->map(fn (NavigationItem $item) => [
                    'id' => $item->id,
                    'parentId' => $item->parent_id,
                    'linkType' => $item->link_type,
                    'linkValue' => $item->link_value,
                    'enabled' => $item->is_enabled,
                    'order' => $item->display_order,
                    'labels' => $item->translations->mapWithKeys(fn ($t) => [$t->locale => $t->label])->all(),
                ])->all(),
            ])->all(),
            'routeKeys' => ['home', 'about', 'pillars', 'programs', 'news', 'publications',
                'transparency', 'partnerships', 'involve', 'contact'],
        ]);
    }

    public function update(string $locale, Request $request, int $menu): RedirectResponse
    {
        $this->authorize('navigation.update');

        $validated = $request->validate([
            'items' => ['required', 'array', 'max:60'],
            'items.*.id' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'items.*.parent_id' => ['nullable', 'exists:navigation_items,id'],
            'items.*.link_type' => ['required', 'in:internal,external'],
            'items.*.link_value' => ['required', 'string', 'max:255'],
            'items.*.is_enabled' => ['boolean'],
            'items.*.display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'items.*.labels' => ['required', 'array'],
            'items.*.labels.ar' => ['required', 'string', 'max:120'],
            'items.*.labels.en' => ['required', 'string', 'max:120'],
        ]);

        NavigationMenu::query()->findOrFail($menu);

        DB::transaction(function () use ($validated, $menu): void {
            $kept = [];
            foreach ($validated['items'] as $item) {
                $linkValue = $item['link_type'] === 'external'
                    ? (str_starts_with($item['link_value'], 'https://') ? $item['link_value'] : '#')
                    : $item['link_value'];

                $record = isset($item['id'])
                    ? NavigationItem::query()->where('navigation_menu_id', $menu)->findOrFail($item['id'])
                    : new NavigationItem(['navigation_menu_id' => $menu]);

                $record->fill([
                    'parent_id' => $item['parent_id'] ?? null,
                    'link_type' => $item['link_type'],
                    'link_value' => $linkValue,
                    'is_enabled' => $item['is_enabled'] ?? true,
                    'display_order' => $item['display_order'] ?? 0,
                ]);
                $record->save();
                $kept[] = $record->id;

                foreach ($item['labels'] as $code => $label) {
                    $record->translations()->updateOrCreate(['locale' => $code], ['label' => $label]);
                }
            }

            NavigationItem::query()
                ->where('navigation_menu_id', $menu)
                ->whereNotIn('id', $kept)
                ->delete();
        });

        app(NavigationService::class)->flush();

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }
}
