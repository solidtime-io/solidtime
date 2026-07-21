<?php

declare(strict_types=1);

return [
    'gotenberg' => [
        'url' => env('GOTENBERG_URL'),
        'basic_auth_username' => env('GOTENBERG_BASIC_AUTH_USERNAME'),
        'basic_auth_password' => env('GOTENBERG_BASIC_AUTH_PASSWORD'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        'sso_enabled' => (bool) env('GOOGLE_SSO_ENABLED', false),
        // Comma-separated list of email domains allowed to sign in / be auto-provisioned via Google SSO.
        // Empty means no domain restriction.
        'sso_allowed_domains' => env('GOOGLE_SSO_ALLOWED_DOMAINS'),
        // Automatically create an account on first Google sign-in (subject to the domain restriction above).
        'sso_auto_provision' => (bool) env('GOOGLE_SSO_AUTO_PROVISION', true),
    ],
];
