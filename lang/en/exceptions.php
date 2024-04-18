<?php

declare(strict_types=1);

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\InactiveUserCanNotBeUsedApiException;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfProjectApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;

return [
    'api' => [
        TimeEntryStillRunningApiException::KEY => 'Time entry is still running',
        UserNotPlaceholderApiException::KEY => 'The given user is not a placeholder',
        TimeEntryCanNotBeRestartedApiException::KEY => 'Time entry is already stopped and can not be restarted',
        InactiveUserCanNotBeUsedApiException::KEY => 'Inactive user can not be used',
        UserIsAlreadyMemberOfProjectApiException::KEY => 'User is already a member of the project',
        EntityStillInUseApiException::KEY => 'The :modelToDelete is still used by a :modelInUse and can not be deleted.',
    ],
];
