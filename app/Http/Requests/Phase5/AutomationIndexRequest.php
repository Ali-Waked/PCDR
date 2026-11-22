<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class AutomationIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('automations.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'trigger_event' => ['sometimes', Rule::in((array) config('phase5.automation.triggers', []))],
            'status' => ['sometimes', Rule::in([
                'pending', 'queued', 'running', 'succeeded', 'failed', 'dead_letter',
            ])],
            'enabled' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        /** @var array<string, mixed> $filters */
        $filters = $this->safe()->only(['trigger_event', 'status', 'enabled', 'page', 'per_page']);

        return $filters;
    }
}
