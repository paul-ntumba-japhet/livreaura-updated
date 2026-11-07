<?php

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'accounts'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'accounts',
        ],
    ],

    'providers' => [
        'accounts' => [
            'driver' => 'account',
            'model' => Account::class,
        ],
    ],

    /*
     | Le broker Laravel n'est pas l'autorité A1 : les défis restent
     | dans defis_verification. Cette entrée évite de pointer vers users.
     */
    'passwords' => [
        'accounts' => [
            'provider' => 'accounts',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
