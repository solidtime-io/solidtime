<?php

return [
    // Global switch to enable PiaDesign adaptations. Keep false by default to minimize behavioral diffs.
    'enabled' => env('PIA_ENABLED', false),

    'alerts' => [
        // Enable reminder/weekly-digest alerts (follow-up implementation)
        'enabled' => env('PIA_ALERTS_ENABLED', false),
        // Days before due_at to notify (e.g., 1, 3, 7). Can be overridden via env
        'reminder_days' => explode(',', env('PIA_ALERTS_REMINDER_DAYS', '1,3,7')),
        // Weekday for weekly digest (monday..sunday)
        'weekly_digest_weekday' => env('PIA_ALERTS_WEEKLY_DIGEST_WEEKDAY', 'monday'),
        // Time of day in 24h format (HH:MM) to send alerts, server timezone
        'send_time' => env('PIA_ALERTS_SEND_TIME', '09:00'),
    ],
];
