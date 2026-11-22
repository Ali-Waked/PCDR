<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

final class PublicationRequest extends ContentRequest
{
    protected string $permission = 'publications.update';

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category' => ['required', 'string', 'in:annual_report,strategic_plan,research,study,needs_assessment,policy,institutional'],
            'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
            'document_media_id' => ['nullable', 'exists:media,id'],
            'cover_media_id' => ['nullable', 'exists:media,id'],
            'publication_date' => ['nullable', 'date'],
            'version' => ['nullable', 'string', 'max:32'],
            'is_restricted' => ['boolean'],
        ]);
    }
}
