<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class WebhookDeliveryIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('integrations.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([
                'pending', 'sending', 'delivered', 'failed', 'dead_letter', 'disabled',
            ])],
            'event' => ['sometimes', Rule::in((array) config('phase5.integrations.webhook_events', []))],
            'endpoint_id' => ['sometimes', 'integer', 'min:1'],
            'deliveryPage' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        /** @var array<string, mixed> $filters */
        $filters = $this->safe()->only(['status', 'event', 'endpoint_id', 'deliveryPage', 'per_page']);

        return $filters;
    }
}
