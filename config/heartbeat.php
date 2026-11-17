<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Heartbeat URLs
    |--------------------------------------------------------------------------
    |
    | URLs of an external monitoring service (push/heartbeat monitors) that are
    | called regularly to signal that the scheduler and the queue are working.
    | If a URL is not set, the corresponding heartbeat is disabled.
    |
    | The queue URL may contain the placeholder "{latency_ms}", which is replaced
    | with the time in milliseconds between dispatching and executing the job.
    |
    */

    'scheduler_url' => env('HEARTBEAT_SCHEDULER_URL'),

    'queue_url' => env('HEARTBEAT_QUEUE_URL'),

    /*
    | Timeout in seconds for the HTTP request to the heartbeat URLs.
    */
    'timeout' => (int) env('HEARTBEAT_TIMEOUT', 5),
];
