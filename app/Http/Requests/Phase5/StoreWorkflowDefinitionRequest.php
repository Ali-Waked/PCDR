<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class StoreWorkflowDefinitionRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('workflows.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:96', 'regex:/^[a-z0-9][a-z0-9_.-]*$/', 'unique:workflow_definitions,key'],
            'category' => ['required', Rule::in(array_values((array) config('phase5.workflow.categories', [])))],
            'status' => [
                'sometimes',
                Rule::in(array_values((array) config('phase5.workflow.statuses', []))),
                $this->approvalValidator(),
            ],
            'trigger_event' => ['sometimes', Rule::in(array_values((array) config('phase5.workflow.triggers', [])))],
            'owner_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            ...$this->translationRules(true),
        ];
    }

    /** @return array<string, mixed> */
    public function definitionInput(): array
    {
        return $this->safe()->only([
            'key', 'category', 'status', 'trigger_event', 'owner_id', 'translations',
        ]);
    }
}
