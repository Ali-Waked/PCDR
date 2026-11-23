<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Privacy-preserving internal notice: no sender email, name or message text
 * is stored in the notification payload.
 */
final class InquiryReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $inquiryType,
        public readonly string $reference,
        public readonly int $inquiryId,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => 'inquiry.received',
            'inquiry_type' => $this->inquiryType,
            'reference' => $this->reference,
            'inquiry_id' => $this->inquiryId,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
