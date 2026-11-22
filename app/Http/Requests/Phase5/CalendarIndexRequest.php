<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class CalendarIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('calendar.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'view' => ['sometimes', Rule::in((array) config('phase5.calendar.views', []))],
            'sources' => ['sometimes', 'array', 'max:'.count((array) config('phase5.calendar.sources', []))],
            'sources.*' => ['string', Rule::in(array_keys((array) config('phase5.calendar.sources', [])))],
            'category' => ['sometimes', 'string', 'max:96'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        /** @var array<string, mixed> $filters */
        $filters = $this->safe()->only(['view', 'sources', 'category', 'from', 'to']);

        return $filters;
    }
}
