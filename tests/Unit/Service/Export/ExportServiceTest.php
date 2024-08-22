<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Export;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Export\ExportService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(ExportService::class)]
#[UsesClass(ExportService::class)]
class ExportServiceTest extends TestCaseWithDatabase
{
    private function getFullOrganization(): Organization
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organization = Organization::factory()->withOwner($user1)->create();
        $member1 = Member::factory()->forUser($user1)->forOrganization($organization)->create();
        $member2 = Member::factory()->forUser($user2)->forOrganization($organization)->create();
        $timeEntry1 = TimeEntry::factory()->forMember($member1)->create();
        $timeEntry2 = TimeEntry::factory()->forMember($member1)->create();
        $project1 = Project::factory()->forOrganization($organization)->create();
        $project2 = Project::factory()->forOrganization($organization)->create();
        $task1 = Task::factory()->forOrganization($organization)->forProject($project1)->create();
        $task2 = Task::factory()->forOrganization($organization)->forProject($project1)->create();
        $task3 = Task::factory()->forOrganization($organization)->forProject($project2)->create();
        $projectMember1 = ProjectMember::factory()->forMember($member1)->forProject($project1)->create();
        $projectMember2 = ProjectMember::factory()->forMember($member2)->forProject($project1)->create();
        $client1 = Client::factory()->forOrganization($organization)->create();
        $client2 = Client::factory()->forOrganization($organization)->create();

        return $organization;
    }

    public function test_export_creates_zip_with_all_the_data_of_the_organization(): void
    {
        // Arrange
        $organization1 = $this->getFullOrganization();
        $organization2 = $this->getFullOrganization();

        // Act
        $exportService = app(ExportService::class);
        $zip = $exportService->export($organization1);

        // Assert
        Storage::disk('local')->assertExists($zip);
    }
}
