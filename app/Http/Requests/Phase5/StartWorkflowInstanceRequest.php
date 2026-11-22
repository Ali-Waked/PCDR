<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\WorkflowDefinition;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StartWorkflowInstanceRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('workflows.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var WorkflowDefinition $definition */
        $definition = $this->route('definition');

        return [
            'version_id' => [
                'required', 'integer',
                Rule::exists('workflow_versions', 'id')->where('definition_id', $definition->getKey()),
            ],
            'subject_type' => ['sometimes', 'nullable', Rule::in(array_values((array) config('phase5.task.linkable_resources', [])))],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'required_with:subject_type'],
            'initiator_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('subject_type')) {
                    return;
                }

                $user = $this->user();
                if ($user === null) {
                    return;
                }

                $this->phase5Auth()->assertCanViewResource(
                    $user,
                    (string) $this->input('subject_type'),
                    (int) $this->input('subject_id'),
                );
            },
        ];
    }

    /** @return array<string, mixed> */
    public function instanceInput(): array
    {
        return $this->safe()->only(['version_id', 'subject_type', 'subject_id', 'initiator_id', 'due_at']);
    }
}
