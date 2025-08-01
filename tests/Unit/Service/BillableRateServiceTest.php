<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillableRateService;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(BillableRateService::class)]
class BillableRateServiceTest extends TestCaseWithDatabase
{
    use RefreshDatabase;

    private BillableRateService $billableRateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billableRateService = app(BillableRateService::class);
    }

    /*
     * Function: updateTimeEntriesBillableRateForProjectMember
     */

    public function test_update_time_entries_billable_rate_for_project_member_updates_time_entries_of_project_member(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create();
        $projectMember = ProjectMember::factory()->forMember($user->member)->forProject($project)->create([
            'billable_rate' => 123,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProjectMember($projectMember);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 123,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_member_updates_time_entries_of_project_member_even_if_all_other_billable_rates_are_set(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $organization = $user->organization;
        $member = $user->member;
        $organization->billable_rate = 111;
        $organization->save();
        $member->billable_rate = 222;
        $member->save();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $projectMember = ProjectMember::factory()->forMember($user->member)->forProject($project)->create([
            'billable_rate' => 123,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProjectMember($projectMember);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 123,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_member_ignores_time_entries_of_other_member(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forUser($otherUser)->forOrganization($user->organization)->create();
        $project = Project::factory()->forOrganization($user->organization)->create();
        $projectMember = ProjectMember::factory()->forMember($user->member)->forProject($project)->create([
            'billable_rate' => 123,
        ]);
        $otherProjectMember = ProjectMember::factory()->forMember($otherMember)->forProject($project)->create([
            'billable_rate' => 321,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($otherMember)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProjectMember($projectMember);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    /*
     * Function: updateTimeEntriesBillableRateForProject
     */

    public function test_update_time_entries_billable_rate_for_project_updates_time_entries_of_project(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProject($project);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 321,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_updates_time_entries_of_project_all_other_billable_rates_null(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $projectMember = ProjectMember::factory()->forMember($user->member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProject($project);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 321,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_ignores_time_entries_that_are_not_billable(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->notBillable()->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProject($project);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => null,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_ignores_time_entries_that_have_project_member_with_billable_rate(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $projectMember = ProjectMember::factory()->forMember($user->member)->forProject($project)->create([
            'billable_rate' => 123,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProject($project);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_project_ignores_time_entries_of_that_project_but_are_incorrectly_attached_to_other_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $userInOtherOrga = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 321,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $brokenTimeEntryInOtherOrganizationButSameProject = TimeEntry::factory()->forMember($userInOtherOrga->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForProject($project);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 2);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 321,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $brokenTimeEntryInOtherOrganizationButSameProject->getKey(),
            'billable_rate' => 1,
        ]);
    }

    /*
     * Function: updateTimeEntriesBillableRateForMember
     */

    public function test_update_time_entries_billable_rate_for_member_updates_time_entries_of_member(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $member->billable_rate = 567;
        $member->save();
        $timeEntry = TimeEntry::factory()->forMember($member)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForMember($member);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 567,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_member_updates_time_entries_of_member_all_other_billable_rates_null(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $member->billable_rate = 110;
        $member->save();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForMember($member);

        // Assert
        $queryLog = DB::getQueryLog();
        $this->assertCount(1, $queryLog);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 110,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_member_ignores_time_entries_that_have_project_member_with_billable_rate(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $member->billable_rate = 110;
        $member->save();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => 123,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForMember($member);

        // Assert
        $queryLog = DB::getQueryLog();
        $this->assertCount(1, $queryLog);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_member_ignores_time_entries_that_have_project_with_billable_rate(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $member->billable_rate = 110;
        $member->save();
        $project = Project::factory()->forOrganization($user->organization)->create([
            'billable_rate' => 123,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForMember($member);

        // Assert
        $queryLog = DB::getQueryLog();
        $this->assertCount(1, $queryLog);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    /*
     * Function: updateTimeEntriesBillableRateForOrganization
     */

    public function test_update_time_entries_billable_rate_for_organization_updates_time_entries_of_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();

        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($user->organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 110,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_updates_time_entries_of_organization_all_other_billable_rates_null(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create([
            'billable_rate' => null,
        ]);

        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($user->organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 110,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_ignores_time_entries_that_are_not_billable(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->notBillable()->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => null,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_ignores_time_entries_of_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $otherUser = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($otherUser->member)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_ignores_time_entries_with_member_with_billable_rate(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $member->billable_rate = 120;
        $member->save();
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();

        $timeEntry = TimeEntry::factory()->forMember($member)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_ignores_time_entries_with_project_with_billable_rate(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 120,
        ]);

        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    public function test_update_time_entries_billable_rate_for_organization_ignores_time_entries_with_project_member_with_billable_rate(): void
    {
        $user = $this->createUserWithPermission();
        $member = $user->member;
        $organization = $user->organization;
        $organization->billable_rate = 110;
        $organization->save();
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create([
            'billable_rate' => 120,
        ]);

        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->billableRate(1)->create();
        $this->enableQueryLog();

        // Act
        $this->billableRateService->updateTimeEntriesBillableRateForOrganization($organization);

        // Assert
        $this->assertQueryCount(1);
        $this->assertDatabaseCount(TimeEntry::class, 1);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'billable_rate' => 1,
        ]);
    }

    /*
     * Function: getBillableRateForTimeEntryWithGivenRelations
     */

    public function test_billable_rate_is_null_if_time_entry_is_not_billable(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => 4004,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => false,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(null, $billableRate);
    }

    public function test_billable_rate_uses_project_member_rate_as_first_priority(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => 4004,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(4004, $billableRate);
    }

    public function test_billable_rate_uses_project_rate_as_second_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(3003, $billableRate);
    }

    public function test_billable_rate_uses_project_rate_as_second_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(3003, $billableRate);
    }

    public function test_billable_rate_uses_organization_member_rate_as_third_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(2002, $billableRate);
    }

    public function test_billable_rate_uses_organization_member_rate_as_third_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(2002, $billableRate);
    }

    public function test_billable_rate_uses_organization_rate_as_fourth_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(1001, $billableRate);
    }

    public function test_billable_rate_uses_organization_rate_as_fourth_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(1001, $billableRate);
    }

    public function test_billable_rate_is_null_if_billable_rate_on_all_levels_are_null(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => null,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntry($timeEntry);

        // Assert
        $this->assertSame(null, $billableRate);
    }

    public function test_billable_rate_with_given_relations_returns_null_if_not_billable(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => 4004,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => false,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(null, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_project_member_rate_as_first_priority(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => 4004,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(4004, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_project_rate_as_second_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(3003, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_project_rate_as_second_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => 3003,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            null,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(3003, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_organization_member_rate_as_third_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(2002, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_organization_member_rate_as_third_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => 2002,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            null,
            null,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(2002, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_organization_rate_as_fourth_priority_using_null_values_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(1001, $billableRate);
    }

    public function test_billable_rate_with_given_relations_uses_organization_rate_as_fourth_priority_using_non_existing_entities_before(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => 1001,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            null,
            null,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(1001, $billableRate);
    }

    public function test_billable_rate_with_given_relations_is_null_if_billable_rate_on_all_levels_are_null(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'billable_rate' => null,
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create([
            'billable_rate' => null,
        ]);
        $project = Project::factory()->forOrganization($organization)->create([
            'billable_rate' => null,
        ]);
        $projectMember = ProjectMember::factory()->forMember($member)->forProject($project)->create([
            'billable_rate' => null,
        ]);
        $timeEntry = TimeEntry::factory()->forProject($project)->forMember($member)->forOrganization($organization)->create([
            'billable' => true,
        ]);
        $this->enableQueryLog();

        // Act
        $billableRate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
            $timeEntry,
            $projectMember,
            $project,
            $member,
            $organization
        );

        // Assert
        $this->assertQueryCount(0);
        $this->assertSame(null, $billableRate);
    }
}
