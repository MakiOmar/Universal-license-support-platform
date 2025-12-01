<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows API calls from any localhost origin, including
    | Vite / Nuxt / other npm dev servers running on arbitrary ports.
    | For production, you should restrict these to your real domains.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://127.0.0.1',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost(:[0-9]+)?$/',
        '/^http:\/\/127\.0\.0\.1(:[0-9]+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];


