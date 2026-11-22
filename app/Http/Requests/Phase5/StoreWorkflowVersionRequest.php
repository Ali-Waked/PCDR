<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\WorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

final class StoreWorkflowVersionRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('workflows.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxSteps = (int) config('phase5.workflow.max_steps', 25);

        return [
            'steps' => ['required', 'array', 'min:1', 'max:'.$maxSteps],
            'steps.*' => ['required', 'array'],
            'steps.*.key' => ['required', 'string', 'max:96', 'regex:/^[a-z0-9][a-z0-9_.-]*$/'],
            'steps.*.label' => ['required', 'string', 'max:255'],
            'steps.*.label_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'steps.*.label_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'steps.*.order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'steps.*.assignment_type' => [
                'sometimes',
                Rule::in(array_values((array) config('phase5.workflow.assignment_types', []))),
            ],
            'steps.*.assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'steps.*.role_key' => ['sometimes', 'nullable', 'string', 'max:96'],
            'steps.*.due_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
            'steps.*.due_at' => ['sometimes', 'nullable', 'date'],
            'steps.*.completion_condition' => ['sometimes', 'nullable', 'array'],
            'steps.*.completion_condition.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'steps.*.notification_event' => [
                'sometimes', 'nullable', 'string', 'max:96', $this->approvalValidator(),
            ],
            'steps.*.allow_rejection' => ['sometimes', 'boolean'],
            'steps.*.allow_return' => ['sometimes', 'boolean'],
            'steps.*.requires_comment' => ['sometimes', 'boolean'],
            'steps.*.metadata' => ['sometimes', 'nullable', 'array'],
            'steps.*.metadata.*' => ['sometimes', 'nullable', 'string', 'max:255'],
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

                try {
                    app(WorkflowService::class)->sanitizeSteps((array) $this->input('steps'));
                } catch (InvalidArgumentException $exception) {
                    $validator->errors()->add('steps', $exception->getMessage());
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function versionInput(): array
    {
        return $this->safe()->only(['steps']);
    }
}
