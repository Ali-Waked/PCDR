<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\StrategicPillar;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

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

    protected function options(): array
    {
        return [
            'categories' => ArticleCategory::query()->with('translations')->orderBy('display_order')->get()
                ->map(fn ($category) => ['id' => $category->id, 'label' => $category->t('name')])->all(),
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn ($pillar) => ['id' => $pillar->id, 'label' => $pillar->t('title')])->all(),
            'tags' => Tag::query()->with('translations')->get()
                ->map(fn ($tag) => ['id' => $tag->id, 'label' => $tag->t('name') ?: $tag->slug])->all(),
        ];
    }
}
