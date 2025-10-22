<?php

return [
    // Lead-time defaults and alert windows
    'default_leadtime_days' => env('PLANNER_DEFAULT_LEADTIME_DAYS', 42),
    'alert_window_days' => env('PLANNER_ALERT_WINDOW_DAYS', 10),
    // Planner feature gate (UI/API). Defaults to PIA_ENABLED to keep a single env flag.
    'enabled' => env('PLANNER_ENABLED', env('PIA_ENABLED', false)),
];
