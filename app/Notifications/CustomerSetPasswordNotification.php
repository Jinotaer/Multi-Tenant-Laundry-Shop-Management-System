<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerSetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $url,
        public string $token,
        public string $shopName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Set Your Password - {$this->shopName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An account has been created for you at {$this->shopName}.")
            ->line('Use the button below to choose your password and activate your customer login.')
            ->action('Set Your Password', $this->url)
            ->line('This link will expire in '.config('auth.passwords.customers.expire', 60).' minutes.')
            ->line('If you were not expecting this email, you can ignore it.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => $this->url,
            'token' => $this->token,
        ];
    }
}
