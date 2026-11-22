<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;

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
}
