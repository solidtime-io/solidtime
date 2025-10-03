<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class OverlappingTimeEntryApiException extends ApiException
{
    public const string KEY = 'overlapping_time_entry';
}
