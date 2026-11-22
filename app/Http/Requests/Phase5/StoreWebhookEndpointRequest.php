<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\IntegrationRegistry;
use App\Services\Phase5\WebhookService;
use Closure;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

final class StoreWebhookEndpointRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('integrations.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'integration_id' => ['required', 'integer', 'min:1', 'exists:integration_registries,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required', 'string', 'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        WebhookService::validateUrl((string) $value);
                    } catch (InvalidArgumentException) {
                        $fail(__('phase5.ssrf_blocked'));
                    }
                },
            ],
            'events' => ['required', 'array', 'min:1', 'max:'.count((array) config('phase5.integrations.webhook_events', []))],
            'events.*' => ['string', Rule::in((array) config('phase5.integrations.webhook_events', []))],
            'secret' => [
                'required', 'string',
                'min:'.(int) config('phase5.integrations.secret_min_length', 32),
                'max:255',
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function endpointInput(): array
    {
        /** @var array<string, mixed> $input */
        $input = $this->safe()->only(['name', 'url', 'events', 'secret', 'active']);
        $input['active'] = $this->boolean('active');

        return $input;
    }

    public function integration(): IntegrationRegistry
    {
        $integration = IntegrationRegistry::query()->find($this->integer('integration_id'));

        if (! $integration instanceof IntegrationRegistry) {
            abort(404, __('phase5.not_found'));
        }

        return $integration;
    }
}
