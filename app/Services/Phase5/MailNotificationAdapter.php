<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Contracts\Phase5\ExternalNotificationAdapter;
use App\Models\User;
use App\Notifications\Phase5\Phase5Mail;
use Illuminate\Support\Facades\Mail;

/** Queues a content-free mailable; a queued message is never reported as delivered. */
final class MailNotificationAdapter implements ExternalNotificationAdapter
{
    public function __construct(private readonly string $channel = 'mail') {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function dispatch(array $recipient, array $message): array
    {
        $user = User::query()->findOrFail((int) $recipient['user_id']);

        Mail::to($user->email)->send(new Phase5Mail(
            (string) ($message['title_key'] ?? ''),
            (string) ($message['preview_key'] ?? ''),
            $user->locale,
        ));

        return [
            'status' => 'queued',
            'provider' => 'local-mail',
            'provider_reference' => null,
            'error_code' => null,
        ];
    }
}
