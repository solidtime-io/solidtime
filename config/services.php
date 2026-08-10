<?php

declare(strict_types=1);

return [
    'gotenberg' => [
        'url' => env('GOTENBERG_URL'),
        'basic_auth_username' => env('GOTENBERG_BASIC_AUTH_USERNAME'),
        'basic_auth_password' => env('GOTENBERG_BASIC_AUTH_PASSWORD'),
    ],
    'api' => [
        'authenticated' => (int) env('API_RATE_LIMIT_AUTH_PER_MINUTE', 200),
        'guest' => (int) env('API_RATE_LIMIT_GUEST_PER_MINUTE', 60),
    ],
];
