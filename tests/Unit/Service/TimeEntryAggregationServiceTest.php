<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\Weekday;
use App\Models\TimeEntry;
use App\Service\TimeEntryAggregationService;
use Illuminate\Support\Carbon;
use Tests\TestCaseWithDatabase;

class TimeEntryAggregationServiceTest extends TestCaseWithDatabase
{
    private TimeEntryAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimeEntryAggregationService::class);
    }

    public function test_aggregate_time_entries_by_day_and_project_returns_empty_array_if_no_time_entries_given(): void
    {
        // Arrange
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Day,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null
        );

        // Assert
        $this->assertSame([
            'seconds' => 0,
            'cost' => 0,
            'grouped_type' => 'day',
            'grouped_data' => [],
        ], $result);
    }

    public function test_aggregate_time_entries_by_day_and_project_with_filled_gaps(): void
    {
        // Arrange
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Day,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            true,
            Carbon::now()->subDays(2)->utc(),
            Carbon::now()->subDays(1)->utc(),
        );

        // Assert
        $this->assertSame([
            'seconds' => 0,
            'cost' => 0,
            'grouped_type' => 'day',
            'grouped_data' => [
                [
                    'key' => Carbon::now()->subDays(2)->utc()->format('Y-m-d'),
                    'seconds' => 0,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [],
                ],
                [
                    'key' => Carbon::now()->subDays(1)->utc()->format('Y-m-d'),
                    'seconds' => 0,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [],
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_entries_by_user_and_project_with_filled_gaps(): void
    {
        // Arrange
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::User,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            true,
            Carbon::now()->subDays(2),
            Carbon::now()->subDays(1),
        );

        // Assert
        $this->assertSame([
            'seconds' => 0,
            'cost' => 0,
            'grouped_type' => 'user',
            'grouped_data' => [],
        ], $result);
    }

    public function test_aggregate_time_entries_by_user_and_day_with_filled_gaps(): void
    {
        // Arrange
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::User,
            TimeEntryAggregationType::Day,
            'Europe/Vienna',
            Weekday::Monday,
            true,
            Carbon::now()->subDays(2),
            Carbon::now()->subDays(1),
        );

        // Assert
        $this->assertSame([
            'seconds' => 0,
            'cost' => 0,
            'grouped_type' => 'user',
            'grouped_data' => [],
        ], $result);
    }
}
