<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillingContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(Organization::class)]
#[CoversClass(Project::class)]
#[CoversClass(ProjectMember::class)]
#[CoversClass(Member::class)]
#[CoversClass(TimeEntry::class)]
class BillableRateRestrictionTest extends TestCaseWithDatabase
{
    use RefreshDatabase;

    public function test_new_time_entry_is_not_billable_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
        $this->actAsOrganizationWithoutBillableRates();

        // Act
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->billable()->create();

        // Assert
        $this->assertFalse($timeEntry->refresh()->billable);
    }

    public function test_existing_time_entry_can_keep_and_remove_billable_flag_but_not_get_it_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
        $billableTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->billable()->create();
        $removedBillableTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->billable()->create();
        $nonBillableTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->notBillable()->create();
        $this->actAsOrganizationWithoutBillableRates();

        // Act
        $billableTimeEntry->description = 'Changed';
        $billableTimeEntry->save();
        $removedBillableTimeEntry->billable = false;
        $removedBillableTimeEntry->save();
        $nonBillableTimeEntry->billable = true;
        $nonBillableTimeEntry->save();

        // Assert
        $this->assertTrue($billableTimeEntry->refresh()->billable);
        $this->assertFalse($removedBillableTimeEntry->refresh()->billable);
        $this->assertFalse($nonBillableTimeEntry->refresh()->billable);
    }

    public function test_time_entry_can_get_billable_flag_if_organization_can_use_billable_rates(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->notBillable()->create();
        $this->actAsOrganizationWithSubscription();

        // Act
        $timeEntry->billable = true;
        $timeEntry->save();

        // Assert
        $this->assertTrue($timeEntry->refresh()->billable);
    }

    public function test_billing_is_only_asked_if_a_restricted_change_is_saved(): void
    {
        // Arrange
        $organization = Organization::factory()->billableRate(10000)->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->billableRate(10000)->create();
        $project = Project::factory()->forOrganization($organization)->billable(10000)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create(['billable_rate' => 10000]);
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->billable()->create();
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('canUseBillableRates');
        });

        // Act
        $organization->name = 'Changed';
        $organization->save();
        $member->billable_rate = 10000;
        $member->save();
        $project->name = 'Changed';
        $project->is_billable = true;
        $project->save();
        $projectMember->billable_rate = 10000;
        $projectMember->save();
        $timeEntry->description = 'Changed';
        $timeEntry->billable = true;
        $timeEntry->save();
        $timeEntry->billable = false;
        $timeEntry->save();
        TimeEntry::factory()->forOrganization($organization)->forMember($member)->notBillable()->create();

        // Assert
        $this->assertFalse($timeEntry->refresh()->billable);
    }

    public function test_billable_rates_can_not_be_set_or_changed_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $organization = Organization::factory()->billableRate(10000)->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->billableRate(10000)->create();
        $project = Project::factory()->forOrganization($organization)->billable(10000)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create(['billable_rate' => 10000]);
        $this->actAsOrganizationWithoutBillableRates();

        // Act
        $organization->billable_rate = 20000;
        $organization->save();
        $member->billable_rate = null;
        $member->save();
        $project->billable_rate = 20000;
        $project->save();
        $projectMember->billable_rate = null;
        $projectMember->save();
        $newProject = Project::factory()->forOrganization($organization)->billable(10000)->create();
        $newProjectMember = ProjectMember::factory()->forProject($newProject)->forMember($member)->create(['billable_rate' => 10000]);

        // Assert
        $this->assertSame(10000, $organization->refresh()->billable_rate);
        $this->assertSame(10000, $member->refresh()->billable_rate);
        $this->assertSame(10000, $project->refresh()->billable_rate);
        $this->assertTrue($project->is_billable);
        $this->assertSame(10000, $projectMember->refresh()->billable_rate);
        $newProject->refresh();
        $this->assertNull($newProject->billable_rate);
        $this->assertFalse($newProject->is_billable);
        $this->assertNull($newProjectMember->refresh()->billable_rate);
    }

    public function test_employees_can_not_be_allowed_to_see_billable_rates_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $organization = Organization::factory()->create(['employees_can_see_billable_rates' => false]);
        $this->actAsOrganizationWithoutBillableRates();

        // Act
        $organization->employees_can_see_billable_rates = true;
        $organization->save();

        // Assert
        $this->assertFalse($organization->refresh()->employees_can_see_billable_rates);
    }
}
