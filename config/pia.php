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
    ],

    'templates' => [
        // Auto-create tasks for new projects based on templates
        'auto_seed' => env('PIA_TEMPLATES_AUTO_SEED', true),
        // Optional DB-backed templates; if none exist, fall back to defaults below
        'defaults' => [
            [
                'name' => 'Implementation (site and supplier schedule checkpoints)',
                'milestones' => [
                    ['name' => 'Estimated Completion Date', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'First Fix Plumb/Elec Required', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Wood Flooring Required', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Second Fix Plumbing Required', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Decorative Lighting Required', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Plastering Completed', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Hard Flooring Laid', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Paints & Grouts Confirmed', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Joinery Templating', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Curtain Templating', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Carpet Templating', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Bespoke Headboard Measure', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Appliances to Joiners', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Sinks and Taps to Site', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Furniture Access Check', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Long Lead Time Furniture Ordered', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Curtain Check Measure', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Joinery Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Kitchen Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Worktop Templating', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Joinery Handles To Joiners', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => '2nd Fix Plumb / Elec Completed', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Worktop Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Carpet Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Curtain Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Snagging', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Furniture Installation', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Receipts & Aftercare', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Review', 'is_milestone' => true, 'due_offset_days' => null],
                    ['name' => 'Photoshoot', 'is_milestone' => true, 'due_offset_days' => null],
                ],
            ],
        ],
    ],
];
