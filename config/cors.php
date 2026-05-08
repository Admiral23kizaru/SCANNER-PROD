<?php

return [

    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods'          => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins'          => [
        'http://localhost',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
        'http://localhost:8000',
        'http://58.69.118.16:83',
        'http://58.69.118.16',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers'          => [
        'Content-Type',
        'Authorization',
        'Accept',
        'X-Requested-With',
        'X-Setup-Secret',
    ],

    'exposed_headers'          => [],

    'max_age'                  => 86400,

    'supports_credentials'     => true,

];
