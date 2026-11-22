<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Contracts\Phase5\ExternalNotificationAdapter;

/** Fail-closed adapter for channels without a configured provider. */
final class DisabledNotificationAdapter implements ExternalNotificationAdapter
{
    public function __construct(private readonly string $channel = 'sms') {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function dispatch(array $recipient, array $message): array
    {
        return [
            'status' => 'disabled',
            'provider' => 'none',
            'provider_reference' => null,
            'error_code' => null,
        ];
    }
}
