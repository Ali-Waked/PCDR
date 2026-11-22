<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\TaskChecklistItem;
use Illuminate\Validation\Validator;

final class ToggleChecklistRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $item = $this->route('item');
                $task = $this->routeTask();

                if (! $item instanceof TaskChecklistItem
                    || $task === null
                    || (int) $item->task_id !== (int) $task->getKey()) {
                    abort(404, __('phase5.not_found'));
                }
            },
        ];
    }
}
