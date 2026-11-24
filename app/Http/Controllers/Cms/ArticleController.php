<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\StrategicPillar;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ArticleController extends ContentController
{
    protected string $modelClass = Article::class;

    protected string $resource = 'news';

    protected string $label = 'News and activities';

    protected string $routeBase = 'cms.news';

    protected string $foreignKey = 'article_id';

    /** News uses `excerpt` rather than the shared `summary` column. */
    protected array $translationFields = [
        'title', 'slug', 'excerpt', 'body', 'seo_title', 'seo_description',
    ];

    protected array $extraFillable = [
        'article_category_id', 'strategic_pillar_id', 'cover_media_id',
        'event_date', 'is_featured',
    ];

    protected array $extraRules = [
        'article_category_id' => ['nullable', 'exists:article_categories,id'],
        'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
        'cover_media_id' => ['nullable', 'exists:media,id'],
        'event_date' => ['nullable', 'date'],
        'is_featured' => ['boolean'],
        'tags' => ['nullable', 'array', 'max:12'],
        'tags.*' => ['integer', 'exists:tags,id'],
    ];

    protected function afterSave(Model $record, array $data): void
    {
        /** @var Article $record */
        $record->tags()->sync($data['tags'] ?? []);
    }

    /** @return list<string> */
    protected function indexRelations(): array
    {
        return ['translations', 'category', 'pillar', 'tags', 'cover'];
    }

    protected function applyIndexFilters(Builder $query, Request $request): void
    {
        $categoryId = $request->integer('category');
        if ($categoryId > 0) {
            $query->where('article_category_id', $categoryId);
        }

        $pillarId = $request->integer('pillar');
        if ($pillarId > 0) {
            $query->where('strategic_pillar_id', $pillarId);
        }

        $tagId = $request->integer('tag');
        if ($tagId > 0) {
            $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery->where('tags.id', $tagId));
        }

        $featured = $request->string('featured')->toString();
        if ($featured === '1' || $featured === '0') {
            $query->where('is_featured', $featured === '1');
        }
    }

    /** @return array<string, mixed> */
    protected function indexItem(Model $item, string $locale): array
    {
        /** @var Article $item */
        $categoryName = $item->category?->t('name', $locale) ?: $item->category?->t('name');
        $pillarTitle = $item->pillar?->t('title', $locale) ?: $item->pillar?->t('title');
        $tags = $item->tags->take(4)->map(fn (Tag $tag): string => (string) ($tag->t('name', $locale) ?: $tag->t('name') ?: $tag->slug))->all();

        return array_merge(parent::indexItem($item, $locale), [
            'category' => (string) ($categoryName ?? ''),
            'pillar' => (string) ($pillarTitle ?? ''),
            'isFeatured' => (bool) $item->is_featured,
            'eventDate' => $item->event_date?->toDateString(),
            'tags' => $tags,
            'cover' => $item->cover ? [
                'url' => $item->cover->url(),
                'alt' => $item->cover->t('alt_text', $locale) ?: $item->cover->t('alt_text'),
            ] : null,
        ]);
    }

    /**
     * Return the selected tags so editing an article preserves them.
     *
     * @return array<string, mixed>
     */
    protected function recordAttributes(Model $record): array
    {
        /** @var Article $record */
        return array_merge(parent::recordAttributes($record), [
            'tags' => $record->tags()->pluck('tags.id')->all(),
        ]);
    }

    /** @return list<array{key: string, label: string, options: list<array{value: string|int, label: string}>}> */
    protected function filterOptions(string $locale): array
    {
        return [
            [
                'key' => 'category',
                'label' => (string) __('website.ui.cms_content_page.filters.category', [], $locale),
                'options' => ArticleCategory::query()->with('translations')->orderBy('display_order')->get()
                    ->map(fn (ArticleCategory $category): array => [
                        'value' => $category->id,
                        'label' => (string) ($category->t('name', $locale) ?: $category->t('name')),
                    ])->all(),
            ],
            [
                'key' => 'pillar',
                'label' => (string) __('website.ui.cms_content_page.filters.pillar', [], $locale),
                'options' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                    ->map(fn (StrategicPillar $pillar): array => [
                        'value' => $pillar->id,
                        'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title') ?: $pillar->code),
                    ])->all(),
            ],
            [
                'key' => 'tag',
                'label' => (string) __('website.ui.cms_content_page.filters.tag', [], $locale),
                'options' => Tag::query()->with('translations')->orderBy('id')->get()
                    ->map(fn (Tag $tag): array => [
                        'value' => $tag->id,
                        'label' => (string) ($tag->t('name', $locale) ?: $tag->t('name') ?: $tag->slug),
                    ])->all(),
            ],
            [
                'key' => 'featured',
                'label' => (string) __('website.ui.cms_content_page.filters.featured', [], $locale),
                'options' => [
                    ['value' => '1', 'label' => (string) __('website.ui.cms_content_page.filters.featured_yes', [], $locale)],
                    ['value' => '0', 'label' => (string) __('website.ui.cms_content_page.filters.featured_no', [], $locale)],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function options(string $locale): array
    {
        return [
            'categories' => ArticleCategory::query()->with('translations')->orderBy('display_order')->get()
                ->map(fn (ArticleCategory $category): array => [
                    'id' => $category->id,
                    'label' => (string) ($category->t('name', $locale) ?: $category->t('name')),
                ])->all(),
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn (StrategicPillar $pillar): array => [
                    'id' => $pillar->id,
                    'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title')),
                ])->all(),
            'tags' => Tag::query()->with('translations')->get()
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'label' => (string) ($tag->t('name', $locale) ?: $tag->t('name') ?: $tag->slug),
                ])->all(),
        ];
    }
}
