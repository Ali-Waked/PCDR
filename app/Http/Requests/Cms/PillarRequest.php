<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

final class PillarRequest extends ContentRequest
{
    protected string $permission = 'pillars.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'code' => ['required', 'string', 'size:2'],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'is_featured' => ['boolean'],
            'translations.*.short_title' => ['nullable', 'string', 'max:120'],
        ]);
    }
}
