<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Exceptions\InvalidDomainTransition;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TransitionWorkflowInstanceRequest extends Phase5Request
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
                Rule::in(array_values((array) config('phase5.workflow.statuses', []))),
                $this->approvalValidator(),
            ],
            'action' => [
                'sometimes', 'string', 'max:96', $this->approvalValidator(),
                Rule::in(['start', 'advance', 'return', 'suspend', 'resume', 'cancel', 'close', 'archive']),
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
                $instance = $this->routeInstance();
                if ($user === null || $instance === null) {
                    return;
                }

                try {
                    $this->phase5Auth()->assertTransition(
                        $user,
                        'workflow',
                        (string) $instance->status,
                        (string) $this->input('to_status'),
                    );
                } catch (InvalidDomainTransition) {
                    $validator->errors()->add('to_status', __('phase5.invalid_transition'));
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function transitionInput(): array
    {
        return $this->safe()->only(['to_status', 'action', 'reason']);
    }
}
