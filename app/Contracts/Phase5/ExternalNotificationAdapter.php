<?php

declare(strict_types=1);

namespace App\Contracts\Phase5;

interface ExternalNotificationAdapter
{
    /**
     * @param  array{user_id: int, locale?: string|null}  $recipient
     * @param  array<string, mixed>  $message
     * @return array{status: string, provider: string, provider_reference?: string|null, error_code?: string|null}
     */
    public function dispatch(array $recipient, array $message): array;

    public function channel(): string;
}
