<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

final class PageRequest extends ContentRequest
{
    protected string $permission = 'pages.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'parent_id' => ['nullable', 'exists:pages,id'],
            'is_system' => ['boolean'],
            'translations.*.canonical_url' => ['nullable', 'url', 'max:500'],
            'translations.*.og_image' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
