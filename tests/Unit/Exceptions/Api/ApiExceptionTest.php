<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions\Api;

use App\Exceptions\Api\ApiException;
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
use App\Exceptions\Api\OverlappingTimeEntryApiException;
use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Exceptions\Api\PersonalAccessClientIsNotConfiguredException;
use App\Exceptions\Api\ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfProjectApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Exceptions\Api\UserResendEmailVerificationNoPendingEmailApiException;
use App\Service\Export\ExportException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    /**
     * @return iterable<string, array{ApiException}>
     */
    public static function expectedApiExceptionProvider(): iterable
    {
        yield CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers::class => [new CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers];
        yield CanNotRemoveOwnerFromOrganization::class => [new CanNotRemoveOwnerFromOrganization];
        yield ChangingRoleOfPlaceholderIsNotAllowed::class => [new ChangingRoleOfPlaceholderIsNotAllowed];
        yield ChangingRoleToPlaceholderIsNotAllowed::class => [new ChangingRoleToPlaceholderIsNotAllowed];
        yield EntityStillInUseApiException::class => [new EntityStillInUseApiException('member', 'time_entry')];
        yield FeatureIsNotAvailableInFreePlanApiException::class => [new FeatureIsNotAvailableInFreePlanApiException];
        yield InactiveUserCanNotBeUsedApiException::class => [new InactiveUserCanNotBeUsedApiException];
        yield InvitationForTheEmailAlreadyExistsApiException::class => [new InvitationForTheEmailAlreadyExistsApiException];
        yield OnlyOwnerCanChangeOwnership::class => [new OnlyOwnerCanChangeOwnership];
        yield OnlyPlaceholdersCanBeMergedIntoAnotherMember::class => [new OnlyPlaceholdersCanBeMergedIntoAnotherMember];
        yield OrganizationHasNoSubscriptionButMultipleMembersException::class => [new OrganizationHasNoSubscriptionButMultipleMembersException];
        yield OrganizationNeedsAtLeastOneOwner::class => [new OrganizationNeedsAtLeastOneOwner];
        yield OverlappingTimeEntryApiException::class => [new OverlappingTimeEntryApiException];
        yield ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException::class => [new ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException];
        yield TimeEntryCanNotBeRestartedApiException::class => [new TimeEntryCanNotBeRestartedApiException];
        yield TimeEntryStillRunningApiException::class => [new TimeEntryStillRunningApiException];
        yield UserIsAlreadyMemberOfOrganizationApiException::class => [new UserIsAlreadyMemberOfOrganizationApiException];
        yield UserIsAlreadyMemberOfProjectApiException::class => [new UserIsAlreadyMemberOfProjectApiException];
        yield UserNotPlaceholderApiException::class => [new UserNotPlaceholderApiException];
        yield UserResendEmailVerificationNoPendingEmailApiException::class => [new UserResendEmailVerificationNoPendingEmailApiException];
    }

    #[DataProvider('expectedApiExceptionProvider')]
    public function test_expected_api_exceptions_are_not_reported(ApiException $exception): void
    {
        $this->assertTrue($exception->report());
    }

    /**
     * @return iterable<string, array{ApiException}>
     */
    public static function operationalApiExceptionProvider(): iterable
    {
        yield PdfRendererIsNotConfiguredException::class => [new PdfRendererIsNotConfiguredException];
        yield PersonalAccessClientIsNotConfiguredException::class => [new PersonalAccessClientIsNotConfiguredException];
        yield ExportException::class => [new ExportException];
    }

    #[DataProvider('operationalApiExceptionProvider')]
    public function test_operational_api_exceptions_are_reported(ApiException $exception): void
    {
        $this->assertFalse($exception->report());
    }
}
