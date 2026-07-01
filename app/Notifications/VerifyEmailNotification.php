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
            ->subject('Confirma tu correo electrónico')
            ->greeting('¡Hola '.$notifiable->name.'!')
            ->line('Gracias por crear tu cuenta. Por favor confirma tu correo electrónico presionando el botón a continuación.')
            ->action('Confirmar Correo Electrónico', $url)
            ->line('Este enlace expirará en 15 minutos.')
            ->line('Si no creaste esta cuenta, puedes ignorar este mensaje.');
    }
}
