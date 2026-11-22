<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HomepageSection;
use App\Models\HomepageSectionItem;

final class HomepageService
{
    /** @return list<array<string, mixed>> */
    public function sections(string $locale): array
    {
        return HomepageSection::query()
            ->where('is_enabled', true)
            ->with('translations', 'items')
            ->orderBy('display_order')
            ->get()
            ->map(fn (HomepageSection $section) => [
                'key' => $section->key,
                'type' => $section->type,
                'eyebrow' => $section->t('eyebrow', $locale),
                'title' => $section->t('title', $locale),
                'body' => $section->t('body', $locale),
                'ctaLabel' => $section->t('cta_label', $locale),
                'ctaLink' => $section->t('cta_link', $locale),
                'items' => $section->items->map(fn (HomepageSectionItem $item) => $this->serializeItem($item, $locale))
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /** @return array<string, mixed>|null */
    private function serializeItem(HomepageSectionItem $item, string $locale): ?array
    {
        $model = $item->resolve();
        if (! $model) {
            return null;
        }

        return [
            'type' => $item->item_type,
            'id' => $model->id,
            'title' => $model->t('title', $locale),
            'summary' => $model->t('summary', $locale) ?? $model->t('description', $locale),
            'url' => method_exists($model, 'route') ? $model->route($locale) : null,
        ];
    }
}
