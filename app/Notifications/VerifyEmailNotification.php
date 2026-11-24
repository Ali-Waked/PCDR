<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Services\EmailMessagePresenter;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

final class VerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        /** @var User $notifiable */
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'locale' => $notifiable->locale ?: 'ar',
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }

    public function toMail($notifiable)
    {
        $presenter = app(EmailMessagePresenter::class);
        $locale = $presenter->normalizeLocale($notifiable->locale ?? null);
        $url = $this->verificationUrl($notifiable);
        $message = self::$toMailCallback
            ? call_user_func(self::$toMailCallback, $notifiable, $url)
            : $this->buildLocalizedMailMessage($url, $locale);

        return $message instanceof MailMessage
            ? $presenter->institutionalMailMessage($message, $locale)
            : $message;
    }

    private function buildLocalizedMailMessage(string $url, string $locale): MailMessage
    {
        return (new MailMessage)
            ->subject((string) __('email.auth.verify_subject', [], $locale))
            ->line((string) __('email.auth.verify_intro', [], $locale))
            ->action((string) __('email.auth.verify_action', [], $locale), $url)
            ->line((string) __('email.auth.verify_outro', [], $locale));
    }
}
