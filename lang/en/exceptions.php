<?php

declare(strict_types=1);

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleOfPlaceholderIsNotAllowed;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\FeatureIsNotAvailableInFreePlanApiException;
use App\Exceptions\Api\InactiveUserCanNotBeUsedApiException;
use App\Exceptions\Api\InvitationForTheEmailAlreadyExistsApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OnlyPlaceholdersCanBeMergedIntoAnotherMember;
use App\Exceptions\Api\OrganizationHasNoSubscriptionButMultipleMembersException;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Exceptions\Api\PersonalAccessClientIsNotConfiguredException;
use App\Exceptions\Api\ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfProjectApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Service\Export\ExportException;

return [
    'api' => [
        TimeEntryStillRunningApiException::KEY => 'Time entry is still running',
        UserNotPlaceholderApiException::KEY => 'The given user is not a placeholder',
        TimeEntryCanNotBeRestartedApiException::KEY => 'Time entry is already stopped and can not be restarted',
        InactiveUserCanNotBeUsedApiException::KEY => 'Inactive user can not be used',
        UserIsAlreadyMemberOfOrganizationApiException::KEY => 'User is already a member of the organization',
        UserIsAlreadyMemberOfProjectApiException::KEY => 'User is already a member of the project',
        EntityStillInUseApiException::KEY => 'The :modelToDelete is still used by a :modelInUse and can not be deleted.',
        CanNotRemoveOwnerFromOrganization::KEY => 'Can not remove owner from organization',
        CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers::KEY => 'Can not delete user who is owner of organization with multiple members. Please delete the organization first.',
        OnlyOwnerCanChangeOwnership::KEY => 'Only owner can change ownership',
        OrganizationNeedsAtLeastOneOwner::KEY => 'Organization needs at least one owner',
        ChangingRoleToPlaceholderIsNotAllowed::KEY => 'Changing role to placeholder is not allowed',
        ExportException::KEY => 'Export failed, please try again later or contact support',
        OrganizationHasNoSubscriptionButMultipleMembersException::KEY => 'Organization has no subscription but multiple members',
        PdfRendererIsNotConfiguredException::KEY => 'PDF renderer is not configured',
        FeatureIsNotAvailableInFreePlanApiException::KEY => 'Feature is not available in free plan',
        PersonalAccessClientIsNotConfiguredException::KEY => 'Personal access client is not configured',
        ChangingRoleOfPlaceholderIsNotAllowed::KEY => 'Changing role of placeholder is not allowed',
        OnlyPlaceholdersCanBeMergedIntoAnotherMember::KEY => 'Only placeholders can be merged into another member',
        ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException::KEY => 'This placeholder can not be invited use the merge tool instead',
        InvitationForTheEmailAlreadyExistsApiException::KEY => 'The email has already been invited to the organization. Please wait for the user to accept the invitation or resend the invitation email.',
    ],
    'unknown_error_in_admin_panel' => 'An unknown error occurred. Please check the logs.',
];
