<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Import payload limit
    |--------------------------------------------------------------------------
    |
    | Maximum length of the base64 encoded "data" field of an import request in
    | bytes. Requests with a larger payload are rejected with a validation error.
    |
    */

    'max_data_size' => (int) (env('IMPORT_MAX_DATA_SIZE') ?: 50 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Import record limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of records (clients, projects, tasks, time entries, tags
    | and users) a single import can create. Imports that exceed the limit
    | fail and are rolled back. Imports started via the admin panel are not
    | limited. Null disables the limit.
    |
    */

    'max_records' => is_numeric(env('IMPORT_MAX_RECORDS')) ? (int) env('IMPORT_MAX_RECORDS') : null,

    /*
    |--------------------------------------------------------------------------
    | ZIP extraction limits
    |--------------------------------------------------------------------------
    |
    | Limits applied to ZIP based importers before and during extraction to
    | protect the instance against decompression bombs. The uncompressed size
    | is the sum of all files in the archive in bytes.
    |
    */

    'zip_max_files' => (int) (env('IMPORT_ZIP_MAX_FILES') ?: 10000),

    'zip_max_uncompressed_size' => (int) (env('IMPORT_ZIP_MAX_UNCOMPRESSED_SIZE') ?: 500 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Import job
    |--------------------------------------------------------------------------
    |
    | Imports are processed by a queued job. The timeout is in seconds. The
    | queue is the queue name the job is pushed to, null means the default
    | queue of the connection.
    |
    */

    'job_timeout' => (int) (env('IMPORT_JOB_TIMEOUT') ?: 3600),

    'queue' => env('IMPORT_QUEUE'),

    /*
    |--------------------------------------------------------------------------
    | Import file retention
    |--------------------------------------------------------------------------
    |
    | Number of days the uploaded import files are kept after the import
    | finished. The import itself is kept, only the file is deleted.
    |
    */

    'file_retention_days' => (int) (env('IMPORT_FILE_RETENTION_DAYS') ?: 30),

];
