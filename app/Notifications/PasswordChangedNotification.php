<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.changed.subject', locale: $notifiable->locale))
            ->greeting(__('notifications.changed.greeting', ['name' => $notifiable->name], $notifiable->locale))
            ->line(__('notifications.changed.line1', locale: $notifiable->locale))
            ->line(__('notifications.changed.ignore', locale: $notifiable->locale));
    }
}
