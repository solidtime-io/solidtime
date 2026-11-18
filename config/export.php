<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Export job
    |--------------------------------------------------------------------------
    |
    | Exports are processed by a queued job. The timeout is in seconds. The
    | queue is the queue name the job is pushed to, null means the default
    | queue of the connection.
    |
    */

    'job_timeout' => (int) (env('EXPORT_JOB_TIMEOUT') ?: 3600),

    'queue' => env('EXPORT_QUEUE'),

    /*
    |--------------------------------------------------------------------------
    | Export file retention
    |--------------------------------------------------------------------------
    |
    | Number of days the export files are kept after the export finished.
    | The export itself is kept, only the file is deleted.
    |
    */

    'file_retention_days' => (int) (env('EXPORT_FILE_RETENTION_DAYS') ?: 7),

];
