<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $url,
        public string $shopName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reset Your Password - {$this->shopName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You requested a password reset for your {$this->shopName} account.")
            ->action('Reset Password', $this->url)
            ->line('This link will expire in '.config('auth.passwords.customers.expire', 60).' minutes.')
            ->line('If you did not request a password reset, no action is needed.');
    }

    public function toArray(object $notifiable): array
    {
        return ['url' => $this->url];
    }
}
