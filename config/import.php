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
    | ZIP extraction limits
    |--------------------------------------------------------------------------
    |
    | Limits applied to ZIP based importers before and during extraction to
    | protect the instance against decompression bombs. The uncompressed size
    | is the sum of all files in the archive in bytes.
    |
    */

    'zip_max_files' => (int) (env('IMPORT_ZIP_MAX_FILES') ?: 100),

    'zip_max_uncompressed_size' => (int) (env('IMPORT_ZIP_MAX_UNCOMPRESSED_SIZE') ?: 500 * 1024 * 1024),

];
