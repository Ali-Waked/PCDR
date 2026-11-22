<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class StoreAutomationRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('automations.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:96', 'regex:/^[a-z0-9][a-z0-9_.-]*$/i'],
            'category' => ['required', 'string', 'max:48', 'regex:/^[a-z0-9][a-z0-9_.-]*$/i'],
            'trigger_event' => ['required', Rule::in((array) config('phase5.automation.triggers', []))],
            'conditions' => ['sometimes', 'array', 'max:'.(int) config('phase5.automation.max_conditions', 10)],
            'conditions.*' => ['array'],
            'conditions.*.field' => ['required', Rule::in(array_keys((array) config('phase5.automation.fields', [])))],
            'conditions.*.operator' => ['required', Rule::in(array_keys((array) config('phase5.automation.operators', [])))],
            'conditions.*.value' => ['sometimes', 'nullable'],
            'actions' => ['sometimes', 'array', 'max:'.(int) config('phase5.automation.max_actions', 5)],
            'actions.*' => ['array'],
            'actions.*.action' => ['required', Rule::in(array_keys((array) config('phase5.automation.actions', [])))],
            'retry_limit' => ['sometimes', 'integer', 'min:0', 'max:'.(int) config('phase5.automation.max_retries', 3)],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ];
    }

    /** @return array<string, mixed> */
    public function automationInput(): array
    {
        /** @var array<string, mixed> $input */
        $input = $this->safe()->only([
            'key', 'category', 'trigger_event', 'conditions', 'actions', 'retry_limit', 'timeout_seconds',
        ]);

        return $input;
    }
}
