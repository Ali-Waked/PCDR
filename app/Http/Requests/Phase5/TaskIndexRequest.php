<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TaskIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('tasks.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'view' => ['sometimes', Rule::in([
                'my', 'assigned', 'team', 'overdue', 'upcoming',
                'blocked', 'completed', 'project', 'workflow',
            ])],
            'status' => ['sometimes', Rule::in(array_values((array) config('phase5.task.statuses', [])))],
            'priority' => ['sometimes', Rule::in(array_values((array) config('phase5.task.priorities', [])))],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'project_id' => ['sometimes', 'integer', 'min:1', 'required_if:view,project'],
            'workflow_instance_id' => ['sometimes', 'integer', 'min:1', 'required_if:view,workflow'],
            'sort' => ['sometimes', Rule::in([
                'created_at', 'updated_at', 'id', 'code', 'title',
                'status', 'priority', 'due_at', 'start_at',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
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

                if ($this->input('view') === 'project' && $this->filled('project_id')) {
                    $this->phase5Auth()->assertCanViewResource($user, 'project', (int) $this->input('project_id'));
                }
                if ($this->input('view') === 'workflow' && $this->filled('workflow_instance_id')) {
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
    public function filters(): array
    {
        return $this->safe()->only([
            'view', 'status', 'priority', 'search', 'project_id',
            'workflow_instance_id', 'sort', 'direction', 'per_page',
        ]);
    }
}
