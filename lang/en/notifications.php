<?php

return [
    'verify' => [
        'subject' => 'Confirm your email address',
        'greeting' => 'Hello :name!',
        'line1' => 'Thank you for creating your account. Please confirm your email address by clicking the button below.',
        'action' => 'Confirm Email',
        'expiry' => 'This link will expire in 15 minutes.',
        'ignore' => 'If you did not create this account, you can ignore this message.',
    ],
    'reset' => [
        'subject' => 'Reset your password',
        'greeting' => 'Hello :name!',
        'line1' => 'We received a request to reset your password. Click the button below to proceed.',
        'action' => 'Reset Password',
        'expiry' => 'This link will expire in 60 minutes.',
        'ignore' => 'If you did not request this, you can ignore this message.',
    ],
    'changed' => [
        'subject' => 'Your password was changed',
        'greeting' => 'Hello :name!',
        'line1' => 'Your password was successfully changed.',
        'ignore' => 'If you did not change your password, please contact support immediately.',
    ],
];
