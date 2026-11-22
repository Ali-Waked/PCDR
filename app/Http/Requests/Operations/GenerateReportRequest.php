<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Validation\Rule;

final class GenerateReportRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('reports.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in($this->catalog()->reportCategories())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status_filter' => ['nullable', 'string', 'max:64'],
            'format' => ['required', 'string', Rule::in(['html', 'csv'])],
        ];
    }

    /** @return array<string, mixed> */
    public function reportInput(): array
    {
        return $this->safe()->only([
            'category', 'date_from', 'date_to', 'program_id', 'project_id',
            'location_id', 'status_filter', 'format',
        ]);
    }
}
