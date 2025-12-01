<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the application including file upload limits,
    | API rate limits, and other security-related configurations.
    |
    */

    'file_upload' => [
        'max_size' => env('MAX_FILE_SIZE', 10485760), // 10MB in bytes
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/log',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'log'],
    ],

    'api' => [
        'rate_limit' => [
            'default' => [
                'requests_per_minute' => 60,
                'requests_per_hour' => 1000,
                'requests_per_day' => 10000,
            ],
            'tiered' => [
                'free' => [
                    'requests_per_minute' => 60,
                    'requests_per_hour' => 1000,
                    'requests_per_day' => 10000,
                ],
                'paid' => [
                    'requests_per_minute' => 300,
                    'requests_per_hour' => 10000,
                    'requests_per_day' => 100000,
                ],
            ],
        ],
        'key_cache_ttl' => env('API_KEY_CACHE_TTL', 300), // 5 minutes
    ],

    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ],

    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 300), // 5 minutes
        'license_validation_ttl' => env('CACHE_LICENSE_VALIDATION_TTL', 60), // 1 minute
    ],
];

