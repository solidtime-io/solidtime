<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Export;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Export\ExportService;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;
use ZipArchive;

#[CoversClass(ExportService::class)]
class ExportServiceTest extends TestCaseWithDatabase
{
    private function getFullOrganization(): Organization
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organization = Organization::factory()->withOwner($user1)->create();
        OrganizationInvitation::factory()->forOrganization($organization)->create();
        OrganizationInvitation::factory()->forOrganization($organization)->create();
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
        Tag::factory()->forOrganization($organization)->create();
        Tag::factory()->forOrganization($organization)->create();

        return $organization;
    }

    public function test_export_creates_zip_with_all_the_data_of_the_organization(): void
    {
        // Arrange
        $this->mockPrivateStorage();
        $organization1 = $this->getFullOrganization();
        $organization2 = $this->getFullOrganization();

        // Act
        $exportService = app(ExportService::class);
        $zip = $exportService->export($organization1);

        // Assert
        Storage::disk(config('filesystems.default'))->assertExists($zip);
    }

    public function test_export_includes_time_entry_type_in_time_entries_csv(): void
    {
        // Arrange
        $this->mockPrivateStorage();
        $user = User::factory()->create();
        $organization = Organization::factory()->withOwner($user)->create();
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        $workEntry = TimeEntry::factory()->forMember($member)->create();
        $breakEntry = TimeEntry::factory()->forMember($member)->isBreak()->create();

        // Act
        $exportService = app(ExportService::class);
        $zip = $exportService->export($organization);

        // Assert
        $zipArchive = new ZipArchive;
        $zipArchive->open(Storage::disk(config('filesystems.default'))->path($zip));
        $timeEntriesCsv = $zipArchive->getFromName('time_entries.csv');
        $zipArchive->close();
        $this->assertNotFalse($timeEntriesCsv);
        $reader = Reader::createFromString($timeEntriesCsv);
        $reader->setHeaderOffset(0);
        $this->assertContains('type', $reader->getHeader());
        $typesById = collect($reader)->pluck('type', 'id');
        $this->assertSame('work', $typesById[$workEntry->getKey()]);
        $this->assertSame('break', $typesById[$breakEntry->getKey()]);
    }
}
