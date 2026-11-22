<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\Task;
use App\Models\Phase5\WorkflowInstance;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;

abstract class Phase5Request extends FormRequest
{
    protected function phase5Auth(): Phase5Authorization
    {
        return app(Phase5Authorization::class);
    }

    protected function allows(string $permission): bool
    {
        $user = $this->user();

        return $user !== null && $this->phase5Auth()->allows($user, $permission);
    }

    protected function isApprovalToken(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $normalized = mb_strtolower($value);
        foreach ((array) config('phase5.governance.approval_statuses', []) as $status) {
            if ($normalized === mb_strtolower((string) $status)) {
                return true;
            }
        }

        return str_contains($normalized, 'approv');
    }

    protected function approvalValidator(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($this->isApprovalToken($value)) {
                $fail(__('phase5.governance_disabled'));
            }
        };
    }

    /** @return array<string, mixed> */
    protected function translationRules(bool $required = false): array
    {
        $rules = [
            'translations' => [$required ? 'required' : 'sometimes', 'array', 'min:1'],
        ];

        foreach (['ar', 'en'] as $locale) {
            $rules["translations.{$locale}"] = ['sometimes', 'array'];
            $rules["translations.{$locale}.name"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.description"] = ['sometimes', 'nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    protected function routeTask(): ?Task
    {
        $task = $this->route('task');

        return $task instanceof Task ? $task : null;
    }

    protected function routeInstance(): ?WorkflowInstance
    {
        $instance = $this->route('instance');

        return $instance instanceof WorkflowInstance ? $instance : null;
    }

    protected function resourceType(): ?string
    {
        $type = $this->route('resourceType');

        if (! is_string($type)
            || ! in_array($type, array_values((array) config('phase5.collaboration.commentable_resources', [])), true)) {
            return null;
        }

        return $type;
    }

    protected function resourceId(): int
    {
        return (int) $this->route('resourceId');
    }

    protected function assertResourceViewable(): void
    {
        $user = $this->user();
        $type = $this->resourceType();

        if ($user === null || $type === null) {
            abort(404, __('phase5.not_found'));
        }

        $this->phase5Auth()->assertCanViewResource($user, $type, $this->resourceId());
    }

    /** @param list<string> $keys @return array<string, mixed> */
    protected function safeInput(array $keys): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->safe()->only($keys);

        return $validated;
    }
}
