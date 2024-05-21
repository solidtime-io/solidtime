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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillableRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillableRateService $billableRateService;

    public function setUp(): void
    {
        parent::setUp();
        $this->billableRateService = app(BillableRateService::class);
    }

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
}
