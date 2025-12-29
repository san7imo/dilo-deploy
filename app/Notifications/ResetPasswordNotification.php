<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $resetUrl = $this->resetUrl($notifiable);
        $passwordBroker = config('fortify.passwords', config('auth.defaults.passwords'));
        $expire = config('auth.passwords.'.$passwordBroker.'.expire');
        $appName = config('app.name', 'Dilo Records');
        $appUrl = config('app.url') ?: url('/');
        $supportEmail = config('mail.from.address') ?: 'soporte@dilorecords.com';

        return (new MailMessage)
            ->subject('Restablecer contrasena - '.$appName)
            ->view('emails.password-reset', [
                'actionUrl' => $resetUrl,
                'appName' => $appName,
                'appUrl' => $appUrl,
                'expire' => $expire,
                'supportEmail' => $supportEmail,
                'user' => $notifiable,
            ])
            ->text('emails.password-reset-text', [
                'actionUrl' => $resetUrl,
                'appName' => $appName,
                'appUrl' => $appUrl,
                'expire' => $expire,
                'supportEmail' => $supportEmail,
                'user' => $notifiable,
            ]);
    }
}
