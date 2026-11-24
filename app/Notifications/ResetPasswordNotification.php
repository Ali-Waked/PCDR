<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Services\EmailMessagePresenter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        /** @var User $notifiable */
        return url(route('password.reset', [
            'locale' => $notifiable->locale ?: 'ar',
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    public function toMail($notifiable)
    {
        $presenter = app(EmailMessagePresenter::class);
        $locale = $presenter->normalizeLocale($notifiable->locale ?? null);
        $message = self::$toMailCallback
            ? call_user_func(self::$toMailCallback, $notifiable, $this->token)
            : $this->buildLocalizedMailMessage($this->resetUrl($notifiable), $locale);

        return $message instanceof MailMessage
            ? $presenter->institutionalMailMessage($message, $locale)
            : $message;
    }

    private function buildLocalizedMailMessage(string $url, string $locale): MailMessage
    {
        $expire = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );

        return (new MailMessage)
            ->subject((string) __('email.auth.reset_subject', [], $locale))
            ->line((string) __('email.auth.reset_intro', [], $locale))
            ->action((string) __('email.auth.reset_action', [], $locale), $url)
            ->line((string) __('email.auth.reset_expiry', ['count' => $expire], $locale))
            ->line((string) __('email.auth.reset_outro', [], $locale));
    }
}
