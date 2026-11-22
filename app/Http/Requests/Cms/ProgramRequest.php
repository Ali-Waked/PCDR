<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

final class ProgramRequest extends ContentRequest
{
    protected string $permission = 'programs.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'translations.*.geographic_coverage' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
