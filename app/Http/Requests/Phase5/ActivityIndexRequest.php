<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class ActivityIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('dashboards.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', 'max:160'],
            'subject_type' => ['sometimes', 'string', 'max:96'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        /** @var array<string, mixed> $filters */
        $filters = $this->safe()->only(['action', 'subject_type', 'from', 'to', 'per_page']);

        return $filters;
    }
}
