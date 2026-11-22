<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255', $this->approvalValidator()],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.(int) config('phase5.task.max_description_chars', 10000)],
            'priority' => ['sometimes', Rule::in(array_values((array) config('phase5.task.priorities', [])))],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /** @return array<string, mixed> */
    public function taskInput(): array
    {
        return $this->safe()->only(['title', 'description', 'priority', 'start_at', 'due_at']);
    }
}
