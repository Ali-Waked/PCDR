<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use App\Enums\ProjectStatus;

final class ProjectRequest extends ContentRequest
{
    protected string $permission = 'programs.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'program_id' => ['nullable', 'exists:programs,id'],
            'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
            'project_status' => ['required', 'string', 'in:'.implode(',', ProjectStatus::values())],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'translations.*.geographic_coverage' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
