<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateSpentTimeForProject;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;
use Tests\TestCaseWithDatabase;

class RecalculateSpentTimeForProjectTest extends TestCaseWithDatabase
{
    public function test_recalculates_spent_time_for_project(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'spent_time' => 0,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project)->create();
        TimeEntry::factory()->startWithDuration(now(), 11)->forProject($project)->create();

        $project->refresh();
        $recalculateSpentTimeForProject = new RecalculateSpentTimeForProject($project);
        DB::enableQueryLog();

        // Act
        $recalculateSpentTimeForProject->handle();

        // Assert
        self::assertCount(2, DB::getQueryLog());
        $project->refresh();
        self::assertEquals(21, $project->spent_time);
    }

    public function test_does_not_save_project_if_value_is_already_correct(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'spent_time' => 21,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project)->create();
        TimeEntry::factory()->startWithDuration(now(), 11)->forProject($project)->create();

        $project->refresh();
        $recalculateSpentTimeForProject = new RecalculateSpentTimeForProject($project);
        DB::enableQueryLog();

        // Act
        $recalculateSpentTimeForProject->handle();

        // Assert
        self::assertCount(1, DB::getQueryLog());
        $project->refresh();
        self::assertEquals(21, $project->spent_time);
    }
}
