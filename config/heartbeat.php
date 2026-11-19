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
    | The queue heartbeat is skipped if the queue connection uses the "sync"
    | driver, since there is no queue worker to monitor in that case.
    |
    */

    'scheduler_url' => env('HEARTBEAT_SCHEDULER_URL'),

    'queue_url' => env('HEARTBEAT_QUEUE_URL'),

    /*
    | Timeout in seconds for the HTTP request to the heartbeat URLs.
    | An empty or zero value falls back to the default, since a timeout of 0
    | would disable the timeout.
    */
    'timeout' => max(1, (int) (env('HEARTBEAT_TIMEOUT') ?: 5)),
];
