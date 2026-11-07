<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canal de notification A1
    |--------------------------------------------------------------------------
    |
    | simulation : journal technique sans secret (défaut des tests).
    | mail : Laravel Mail (SMTP Gmail en développement local).
    |
    */

    'notification_driver' => env('IDENTITY_NOTIFICATION_DRIVER', 'simulation'),

    'activation_path' => '/inscription/activation',

    'password_reset_path' => '/recuperation',

];
