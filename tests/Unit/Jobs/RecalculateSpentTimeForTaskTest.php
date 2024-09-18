<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;
use Tests\TestCaseWithDatabase;

class RecalculateSpentTimeForTaskTest extends TestCaseWithDatabase
{
    public function test_recalculates_spent_time_for_task(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'spent_time' => 0,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forTask($task)->create();
        TimeEntry::factory()->startWithDuration(now(), 11)->forTask($task)->create();

        $task->refresh();
        $recalculateSpentTimeForTask = new RecalculateSpentTimeForTask($task);
        DB::enableQueryLog();

        // Act
        $recalculateSpentTimeForTask->handle();

        // Assert
        self::assertCount(2, DB::getQueryLog());
        $task->refresh();
        self::assertEquals(21, $task->spent_time);
    }

    public function test_does_not_save_task_if_value_is_already_correct(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'spent_time' => 21,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forTask($task)->create();
        TimeEntry::factory()->startWithDuration(now(), 11)->forTask($task)->create();

        $task->refresh();
        $recalculateSpentTimeForTask = new RecalculateSpentTimeForTask($task);
        DB::enableQueryLog();

        // Act
        $recalculateSpentTimeForTask->handle();

        // Assert
        self::assertCount(1, DB::getQueryLog());
        $task->refresh();
        self::assertEquals(21, $task->spent_time);
    }
}
