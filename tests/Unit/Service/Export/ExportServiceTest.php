<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Export;

use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Exceptions\Api\ExportAlreadyInProgressApiException;
use App\Jobs\ProcessExport;
use App\Models\Client;
use App\Models\Export;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Export\ExportException;
use App\Service\Export\ExportService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
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

    public function test_create_organization_export_creates_pending_export_and_dispatches_job(): void
    {
        // Arrange
        Queue::fake();
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        // Act
        $export = app(ExportService::class)->createOrganizationExport($organization, $user);

        // Assert
        $export->refresh();
        $this->assertSame(ExportType::Organization, $export->type);
        $this->assertSame(ExportStatus::Pending, $export->status);
        $this->assertSame($organization->getKey(), $export->organization_id);
        $this->assertSame($user->getKey(), $export->user_id);
        $this->assertNull($export->file_path);
        Queue::assertPushed(ProcessExport::class, fn (ProcessExport $job): bool => $job->export->is($export));
    }

    public function test_create_organization_export_throws_exception_if_export_is_already_in_progress(): void
    {
        // Arrange
        Queue::fake();
        $organization = Organization::factory()->create();
        Export::factory()->forOrganization($organization)->create();

        // Act
        try {
            app(ExportService::class)->createOrganizationExport($organization, null);
            $this->fail('Expected exception was not thrown');
        } catch (ExportAlreadyInProgressApiException) {
            // Assert
            $this->assertSame(1, Export::query()->count());
            Queue::assertNothingPushed();
        }
    }

    public function test_create_organization_export_allows_new_export_if_previous_exports_are_finished(): void
    {
        // Arrange
        Queue::fake();
        $organization = Organization::factory()->create();
        Export::factory()->forOrganization($organization)->completed()->create();
        Export::factory()->forOrganization($organization)->failed()->create();

        // Act
        $export = app(ExportService::class)->createOrganizationExport($organization, null);

        // Assert
        $this->assertSame(3, Export::query()->count());
        Queue::assertPushed(ProcessExport::class, fn (ProcessExport $job): bool => $job->export->is($export));
    }

    public function test_database_allows_only_one_active_export_per_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        Export::factory()->forOrganization($organization)->create();

        // Assert
        $this->expectException(UniqueConstraintViolationException::class);

        // Act
        Export::factory()->forOrganization($organization)->running()->create();
    }

    public function test_process_creates_export_file_and_marks_export_as_completed(): void
    {
        // Arrange
        $this->mockPrivateStorage();
        $organization = $this->getFullOrganization();
        $export = Export::factory()->forOrganization($organization)->create();

        // Act
        app(ExportService::class)->process($export);

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Completed, $export->status);
        $this->assertSame('exports/'.$organization->getKey().'/'.$export->getKey().'.zip', $export->file_path);
        $this->assertNotNull($export->started_at);
        $this->assertNotNull($export->finished_at);
        $this->assertNull($export->error_message);
        Storage::disk(config('filesystems.private'))->assertExists($export->file_path);
    }

    public function test_process_marks_export_as_failed_with_error_details_if_export_fails(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $export = Export::factory()->forOrganization($organization)->create();
        $this->partialMock(ExportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('export')
                ->once()
                ->andThrow(new ExportException(new RuntimeException('Disk is full')));
        });

        // Act
        app(ExportService::class)->process($export);

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Failed, $export->status);
        $this->assertSame('Export failed, please try again later or contact support', $export->error_message);
        $this->assertNull($export->file_path);
        $this->assertNotNull($export->finished_at);
        $this->assertStringContainsString('RuntimeException: Disk is full', (string) $export->error_details);
    }

    public function test_process_skips_export_that_is_not_pending(): void
    {
        // Arrange
        $export = Export::factory()->completed()->create();
        $filePath = $export->file_path;
        $this->partialMock(ExportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('export');
        });

        // Act
        app(ExportService::class)->process($export);

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Completed, $export->status);
        $this->assertSame($filePath, $export->file_path);
    }

    public function test_mark_as_failed_sets_status_message_and_finished_at(): void
    {
        // Arrange
        $export = Export::factory()->running()->create();

        // Act
        app(ExportService::class)->markAsFailed($export, 'Export could not be finished');

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Failed, $export->status);
        $this->assertSame('Export could not be finished', $export->error_message);
        $this->assertNotNull($export->finished_at);
    }
}
