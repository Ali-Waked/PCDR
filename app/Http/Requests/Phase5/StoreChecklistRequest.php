<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class StoreChecklistRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', $this->approvalValidator()],
            'position' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /** @return array<string, mixed> */
    public function checklistInput(): array
    {
        return $this->safe()->only(['title', 'position']);
    }
}
