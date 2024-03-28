<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class TimeEntryCanNotBeRestartedApiException extends ApiException
{
    public const string KEY = 'time_entry_can_not_be_restarted';
}
