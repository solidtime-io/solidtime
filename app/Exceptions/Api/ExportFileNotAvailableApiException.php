<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class ExportFileNotAvailableApiException extends ApiException
{
    public const string KEY = 'export_file_not_available';

    public function report(): bool
    {
        return true;
    }
}
