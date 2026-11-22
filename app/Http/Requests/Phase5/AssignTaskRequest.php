<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class AssignTaskRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.assign');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function assignmentInput(): array
    {
        return $this->safe()->only(['assignee_id']);
    }
}
