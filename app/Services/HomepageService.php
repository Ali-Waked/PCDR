<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HomepageSection;
use App\Models\HomepageSectionItem;
use Illuminate\Database\Eloquent\Model;

final class HomepageService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function sections(string $locale, bool $onlyEnabled = true): array
    {
        $query = HomepageSection::query()->with('translations', 'items.translations');

        if ($onlyEnabled) {
            $query->where('is_enabled', true);
        }

        return $query
            ->orderBy('display_order')
            ->get()
            ->map(fn (HomepageSection $section) => $this->serializeSection($section, $locale))
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function section(string $key, string $locale): ?array
    {
        $section = HomepageSection::query()
            ->where('key', $key)
            ->with('translations', 'items.translations')
            ->first();

        return $section ? $this->serializeSection($section, $locale) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSection(HomepageSection $section, string $locale): array
    {
        return [
            'key' => $section->key,
            'type' => $section->type,
            'enabled' => (bool) $section->is_enabled,
            'order' => (int) $section->display_order,
            'eyebrow' => $this->translated($section, 'eyebrow', $locale),
            'title' => $this->translated($section, 'title', $locale),
            'body' => $this->translated($section, 'body', $locale),
            'ctaLabel' => $this->translated($section, 'cta_label', $locale),
            'ctaLink' => $this->translated($section, 'cta_link', $locale),
            'items' => $section->items
                ->filter(fn (HomepageSectionItem $item) => (bool) $item->is_published)
                ->map(fn (HomepageSectionItem $item) => $this->serializeItem($item, $locale))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeItem(HomepageSectionItem $item, string $locale): ?array
    {
        if ($item->isInline()) {
            return [
                'key' => $item->item_key ?: 'item-'.$item->id,
                'type' => 'inline',
                'id' => (int) $item->id,
                'sortOrder' => (int) $item->sort_order,
                'title' => $this->translated($item, 'title', $locale),
                'label' => $this->translated($item, 'label', $locale),
                'body' => $this->translated($item, 'body', $locale),
                'display' => $this->translated($item, 'display_value', $locale),
                'url' => $this->translated($item, 'url', $locale),
                'value' => $item->numeric_value === null ? null : (float) $item->numeric_value,
                'published' => true,
            ];
        }

        $model = $item->resolve();

        if (! $model) {
            return null;
        }

        return [
            'key' => $item->item_key ?: $item->item_type.'-'.$model->id,
            'type' => $item->item_type,
            'id' => (int) $model->id,
            'sortOrder' => (int) $item->sort_order,
            'title' => $this->translated($model, 'title', $locale),
            'label' => $this->translated($model, 'short_title', $locale) ?? $this->translated($model, 'title', $locale),
            'body' => $this->translated($model, 'summary', $locale) ?? $this->translated($model, 'description', $locale),
            'display' => null,
            'url' => method_exists($model, 'route') ? $model->route($locale) : null,
            'value' => null,
            'published' => true,
        ];
    }

    private function translated(Model $model, string $field, string $locale): ?string
    {
        return $model->t($field, $locale);
    }
}
