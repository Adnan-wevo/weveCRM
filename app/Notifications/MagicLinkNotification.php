<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Maize\MagicLogin\Notifications\MagicLinkNotification as BaseMagicLinkNotification;

class MagicLinkNotification extends BaseMagicLinkNotification
{
    /**
     * Build the mail message for the magic login link.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your magic sign-in link'))
            ->greeting(__('Hello!'))
            ->line(__('Click the button below to sign in to your account. This link will expire in :minutes minutes and can only be used once.', ['minutes' => 30]))
            ->action(__('Sign in now'), $this->uri)
            ->line(__('If you did not request this link, no action is needed — the link will expire automatically.'));
    }
}
