<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class ExportAlreadyInProgressApiException extends ApiException
{
    public const string KEY = 'export_already_in_progress';

    public function report(): bool
    {
        return true;
    }
}
