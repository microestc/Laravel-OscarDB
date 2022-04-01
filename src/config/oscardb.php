<?php

return [
    'oscardb' => [
        'driver'    => 'oscar',
        'url'       => env('DB_URL'),
        'host'      => env('DB_HOST', '127.0.0.1'),
        'port'      => env('DB_PORT', '2003'),
        'database'  => env('DB_DATABASE', 'osrdb'),
        'username'  => env('DB_USERNAME', 'sysdba'),
        'password'  => env('DB_PASSWORD', ''),
        'charset'   => 'utf8',
        'prefix'    => '',
        'quoting'   => false,
    ],
];
