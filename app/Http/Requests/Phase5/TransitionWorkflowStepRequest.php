<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\WorkflowStep;
use App\Services\Phase5\WorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TransitionWorkflowStepRequest extends Phase5Request
{
    public function authorize(): bool
    {
        $user = $this->user();
        $instance = $this->routeInstance();

        if ($user === null || $instance === null || ! $this->allows('workflows.transition')) {
            return false;
        }

        $this->phase5Auth()->assertCanViewResource($user, 'workflow_instance', (int) $instance->getKey());

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to_status' => [
                'required', 'string',
                Rule::in(array_values((array) config('phase5.workflow.step_statuses', []))),
                $this->approvalValidator(),
            ],
            'action' => [
                'sometimes', 'string', 'max:96', $this->approvalValidator(),
                Rule::in(['start', 'advance', 'return', 'suspend', 'resume', 'cancel', 'close', 'archive']),
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

                $step = $this->route('step');
                $instance = $this->routeInstance();
                if (! $step instanceof WorkflowStep || $instance === null) {
                    return;
                }
                if ((int) $step->instance_id !== (int) $instance->getKey()) {
                    abort(404, __('phase5.not_found'));
                }

                if (! app(WorkflowService::class)->stepTransitionAllowed(
                    (string) $step->status,
                    (string) $this->input('to_status'),
                )) {
                    $validator->errors()->add('to_status', __('phase5.invalid_transition'));
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function transitionInput(): array
    {
        return $this->safe()->only(['to_status', 'action']);
    }
}
