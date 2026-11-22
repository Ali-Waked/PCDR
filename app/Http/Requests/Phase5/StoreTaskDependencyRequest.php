<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\TaskService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreTaskDependencyRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $task = $this->routeTask();

        return [
            'depends_on_task_id' => [
                'required', 'integer', 'min:1', 'exists:tasks,id',
                Rule::notIn([(int) $task?->getKey()]),
            ],
            'type' => [
                'sometimes',
                Rule::in(['finish_to_start', 'start_to_finish', 'start_to_start', 'finish_to_finish']),
            ],
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
                $task = $this->routeTask();
                if ($user === null || $task === null) {
                    return;
                }

                $dependsOnId = (int) $this->input('depends_on_task_id');
                $this->phase5Auth()->assertCanViewResource($user, 'task', $dependsOnId);

                if (app(TaskService::class)->wouldCreateCycle((int) $task->getKey(), $dependsOnId)) {
                    $validator->errors()->add('depends_on_task_id', __('phase5.invalid_transition'));
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function dependencyInput(): array
    {
        return $this->safe()->only(['depends_on_task_id', 'type']);
    }
}
