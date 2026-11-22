<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class ArchiveTaskRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.delete');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
