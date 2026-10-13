<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class TimeEntryMemberCanNotBeChangedWhileRunningApiException extends ApiException
{
    public const string KEY = 'time_entry_member_can_not_be_changed_while_running';

    public function report(): bool
    {
        return true;
    }
}
