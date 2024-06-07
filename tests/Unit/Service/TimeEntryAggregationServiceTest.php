<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\Weekday;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Service\TimeEntryAggregationService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryAggregationService::class)]
#[UsesClass(TimeEntryAggregationService::class)]
class TimeEntryAggregationServiceTest extends TestCaseWithDatabase
{
    private TimeEntryAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimeEntryAggregationService::class);
    }

    public function test_aggregate_time_entries_empty_state_by_day_and_project_returns_empty_array_if_no_time_entries_given(): void
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

    public function test_aggregate_time_entries_empty_state_by_day_and_project_with_filled_gaps(): void
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

    public function test_aggregate_time_entries_empty_state_by_user_and_project_with_filled_gaps(): void
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

    public function test_aggregate_time_entries_empty_state_by_user_and_day_with_filled_gaps(): void
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

    public function test_aggregate_time_entries_by_client_and_project(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        $timeEntry1 = TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create();
        $timeEntry2 = TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create();
        $timeEntry3 = TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project3)->create();
        $timeEntry4 = TimeEntry::factory()->startWithDuration(now(), 10)->create();
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Client,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null
        );

        // Assert
        $this->assertEqualsCanonicalizing([
            'seconds' => 40,
            'cost' => 0,
            'grouped_type' => 'client',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $project3->getKey(),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client1->getKey(),
                    'seconds' => 10,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project1->getKey(),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client2->getKey(),
                    'seconds' => 10,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project2->getKey(),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }
}
