<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Exceptions\InvalidDomainTransition;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TransitionTaskRequest extends TaskActionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeTask('tasks.transition');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to_status' => [
                'required', 'string',
                Rule::in(array_values((array) config('phase5.task.statuses', []))),
                $this->approvalValidator(),
            ],
            'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
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

                $to = (string) $this->input('to_status');

                try {
                    $this->phase5Auth()->assertTransition($user, 'task', (string) $task->status, $to);
                } catch (InvalidDomainTransition) {
                    $validator->errors()->add('to_status', __('phase5.invalid_transition'));

                    return;
                }

                if ((string) $task->status === 'completed' && $to === 'in_progress' && ! $this->filled('reason')) {
                    $validator->errors()->add('reason', __('phase5.invalid_transition'));
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function transitionInput(): array
    {
        return $this->safe()->only(['to_status', 'reason']);
    }
}
