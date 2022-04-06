<?php

return [
    'oscardb' => [
        'driver'    => 'oscar',
        'url'       => env('DB_URL'),
        'host'      => '127.0.0.1',
        'port'      => '2003',
        'database'  => 'osrdb',
        'username'  => 'sysdba',
        'password'  => 'szoscar55',
        'charset'   => 'utf8',
        'prefix'    => '',
        'quoting'   => false,
    ],
];
