<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Correction;

use App\Console\Commands\Correction\CorrectionDeleteTimeEntriesWithEndBeforeStartCommand;
use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\Audit;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(CorrectionDeleteTimeEntriesWithEndBeforeStartCommand::class)]
class CorrectionDeleteTimeEntriesWithEndBeforeStartCommandTest extends TestCaseWithDatabase
{
    public function test_deletes_time_entries_with_end_before_start_and_audits_the_deletion(): void
    {
        // Arrange
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $task = Task::factory()->forOrganization($organization)->forProject($project)->create();
        $invalidTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->forProject($project)->forTask($task)->create([
            'start' => Carbon::parse('2026-08-01T03:00:00Z'),
            'end' => Carbon::parse('2026-08-01T02:59:55Z'),
        ]);
        $validTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->create([
            'start' => Carbon::parse('2026-08-01T04:00:00Z'),
            'end' => Carbon::parse('2026-08-01T04:00:00Z'),
        ]);
        $runningTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->active()->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('correction:delete-time-entries-with-end-before-start');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Deletes all time entries where the end is before the start...\n".
            'Deleted time entry (id='.$invalidTimeEntry->getKey().", start=2026-08-01T03:00:00Z, end=2026-08-01T02:59:55Z)\n", $output);
        $this->assertDatabaseMissing(TimeEntry::class, ['id' => $invalidTimeEntry->getKey()]);
        $this->assertDatabaseHas(TimeEntry::class, ['id' => $validTimeEntry->getKey()]);
        $this->assertDatabaseHas(TimeEntry::class, ['id' => $runningTimeEntry->getKey()]);
        $this->assertSame(1, Audit::query()
            ->where('auditable_type', $invalidTimeEntry->getMorphClass())
            ->where('auditable_id', $invalidTimeEntry->getKey())
            ->where('event', 'deleted')
            ->count());
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
    }

    public function test_does_not_delete_time_entries_in_dry_run(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $invalidTimeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->create([
            'start' => Carbon::parse('2026-08-01T03:00:00Z'),
            'end' => Carbon::parse('2026-08-01T02:59:55Z'),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('correction:delete-time-entries-with-end-before-start --dry-run');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Deletes all time entries where the end is before the start...\n".
            "Running in dry-run mode. Nothing will be saved to the database.\n".
            'Deleted time entry (id='.$invalidTimeEntry->getKey().", start=2026-08-01T03:00:00Z, end=2026-08-01T02:59:55Z)\n", $output);
        $this->assertDatabaseHas(TimeEntry::class, ['id' => $invalidTimeEntry->getKey()]);
    }
}
