<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\EmailMessagePresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AuditLifecycleNotification extends Notification
{
    use Queueable;

    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $status,
        public readonly array $details = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return method_exists($notifiable, 'routeNotificationForDatabase')
            ? ['database', 'mail']
            : ['mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'details' => $this->details,
            'sensitive' => false,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        foreach ($this->details as $label => $value) {
            if (is_scalar($value)) {
                $mail->line((string) $label.': '.(string) $value);
            }
        }

        $presenter = app(EmailMessagePresenter::class);

        return $presenter->institutionalMailMessage(
            $mail,
            $presenter->normalizeLocale($notifiable->locale ?? null),
        );
    }
}
