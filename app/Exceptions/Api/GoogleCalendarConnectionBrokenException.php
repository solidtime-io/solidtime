<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class GoogleCalendarConnectionBrokenException extends ApiException
{
    public const string KEY = 'google_calendar_connection_broken';
}
