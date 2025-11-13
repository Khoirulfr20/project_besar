<?php

return [
    'defaults' => [
    'guard' => 'web', // default guard untuk admin (session)
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session', // untuk admin web
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'sanctum', // ganti dari jwt ke sanctum
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],

];