<?php

return [
    'verify' => [
        'subject' => 'Confirma tu correo electrónico',
        'greeting' => '¡Hola :name!',
        'line1' => 'Gracias por crear tu cuenta. Por favor confirma tu correo electrónico presionando el botón a continuación.',
        'action' => 'Confirmar Correo Electrónico',
        'expiry' => 'Este enlace expirará en 15 minutos.',
        'ignore' => 'Si no creaste esta cuenta, puedes ignorar este mensaje.',
    ],
    'reset' => [
        'subject' => 'Restablece tu contraseña',
        'greeting' => '¡Hola :name!',
        'line1' => 'Recibimos una solicitud para restablecer tu contraseña. Presiona el botón a continuación para continuar.',
        'action' => 'Restablecer Contraseña',
        'expiry' => 'Este enlace expirará en 60 minutos.',
        'ignore' => 'Si no solicitaste esto, puedes ignorar este mensaje.',
    ],
    'changed' => [
        'subject' => 'Tu contraseña fue cambiada',
        'greeting' => '¡Hola :name!',
        'line1' => 'Tu contraseña fue cambiada exitosamente.',
        'ignore' => 'Si no cambiaste tu contraseña, contacta a soporte inmediatamente.',
    ],
];
