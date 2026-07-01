<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = config('app.url').'/email/verify/'.urlencode($this->token);

        return (new MailMessage)
            ->subject(__('notifications.verify.subject', locale: $notifiable->locale))
            ->greeting(__('notifications.verify.greeting', ['name' => $notifiable->name], $notifiable->locale))
            ->line(__('notifications.verify.line1', locale: $notifiable->locale))
            ->action(__('notifications.verify.action', locale: $notifiable->locale), $url)
            ->line(__('notifications.verify.expiry', locale: $notifiable->locale))
            ->line(__('notifications.verify.ignore', locale: $notifiable->locale));
    }
}
