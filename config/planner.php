<?php

return [
    // Planner feature gate (UI/API). Defaults to PIA_ENABLED to keep a single env flag.
    'enabled' => env('PLANNER_ENABLED', env('PIA_ENABLED', false)),
];
