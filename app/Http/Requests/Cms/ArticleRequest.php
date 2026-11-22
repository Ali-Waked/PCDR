<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

final class ArticleRequest extends ContentRequest
{
    protected string $permission = 'news.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
            'cover_media_id' => ['nullable', 'exists:media,id'],
            'event_date' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);
    }
}
