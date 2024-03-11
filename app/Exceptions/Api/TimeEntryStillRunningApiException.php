<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class TimeEntryStillRunningApiException extends ApiException
{
    public const string KEY = 'time_entry_still_running';
}
