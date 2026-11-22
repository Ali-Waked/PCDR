<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\WorkflowDefinition;
use Illuminate\Validation\Rule;

final class UpdateWorkflowDefinitionRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('workflows.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var WorkflowDefinition $definition */
        $definition = $this->route('definition');

        return [
            'key' => [
                'sometimes', 'string', 'max:96', 'regex:/^[a-z0-9][a-z0-9_.-]*$/',
                Rule::unique('workflow_definitions', 'key')->ignore($definition->getKey()),
            ],
            'category' => ['sometimes', Rule::in(array_values((array) config('phase5.workflow.categories', [])))],
            'status' => [
                'sometimes',
                Rule::in(array_values((array) config('phase5.workflow.statuses', []))),
                $this->approvalValidator(),
            ],
            'trigger_event' => ['sometimes', Rule::in(array_values((array) config('phase5.workflow.triggers', [])))],
            'owner_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            ...$this->translationRules(),
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
