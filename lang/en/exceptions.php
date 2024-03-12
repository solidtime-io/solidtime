<?php

declare(strict_types=1);

use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;

return [
    'api' => [
        TimeEntryStillRunningApiException::KEY => 'Time entry is still running',
        UserNotPlaceholderApiException::KEY => 'The given user is not a placeholder',
    ],
];
