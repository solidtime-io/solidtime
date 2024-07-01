<?php

declare(strict_types=1);

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\InactiveUserCanNotBeUsedApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
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
        CanNotRemoveOwnerFromOrganization::KEY => 'Can not remove owner from organization',
        CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers::KEY => 'Can not delete user who is owner of organization with multiple members. Please delete the organization first.',
        OnlyOwnerCanChangeOwnership::KEY => 'Only owner can change ownership',
        OrganizationNeedsAtLeastOneOwner::KEY => 'Organization needs at least one owner',
        ChangingRoleToPlaceholderIsNotAllowed::KEY => 'Changing role to placeholder is not allowed',
    ],
    'unknown_error_in_admin_panel' => 'An unknown error occurred. Please check the logs.',
];
