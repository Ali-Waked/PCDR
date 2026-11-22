<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class InboundWebhookRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'timestamp' => $this->header('X-Phase5-Timestamp'),
            'nonce' => $this->header('X-Phase5-Nonce'),
            'signature' => $this->header('X-Phase5-Signature'),
            'event' => $this->header('X-Phase5-Event'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'timestamp' => ['required', 'numeric'],
            'nonce' => ['required', 'string', 'max:128'],
            'signature' => ['required', 'string', 'max:128'],
            'event' => ['required', Rule::in((array) config('phase5.integrations.webhook_events', []))],
        ];
    }
}
