<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreTaskRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('tasks.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.(int) config('phase5.task.max_description_chars', 10000)],
            'status' => [
                'sometimes', Rule::in(array_values((array) config('phase5.task.statuses', []))),
                $this->approvalValidator(),
            ],
            'priority' => ['sometimes', Rule::in(array_values((array) config('phase5.task.priorities', [])))],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'owner_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'subject_type' => ['sometimes', 'nullable', Rule::in(array_values((array) config('phase5.task.linkable_resources', [])))],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'required_with:subject_type'],
            'workflow_instance_id' => ['sometimes', 'nullable', 'integer', 'exists:workflow_instances,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:tasks,id'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();
                if ($user === null) {
                    return;
                }

                if ($this->filled('subject_type')) {
                    $this->phase5Auth()->assertCanViewResource(
                        $user,
                        (string) $this->input('subject_type'),
                        (int) $this->input('subject_id'),
                    );
                }
                if ($this->filled('workflow_instance_id')) {
                    $this->phase5Auth()->assertCanViewResource(
                        $user,
                        'workflow_instance',
                        (int) $this->input('workflow_instance_id'),
                    );
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function taskInput(): array
    {
        return $this->safe()->only([
            'title', 'description', 'status', 'priority', 'start_at', 'due_at',
            'owner_id', 'subject_type', 'subject_id', 'workflow_instance_id', 'parent_id',
        ]);
    }
}
