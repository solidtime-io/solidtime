<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryRoundingType;
use App\Enums\Weekday;
use App\Models\Client;
use App\Models\Project;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Service\TimeEntryAggregationService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryAggregationService::class)]
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
            null,
            true,
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

    public function test_aggregate_time_entries_by_project_and_description(): void
    {
        // Arrange
        $project1 = Project::factory()->create([
            // Note: To ensure deterministic order
            'id' => '5de4e6df-9560-4675-95be-18d42c441bfc',
        ]);
        $project2 = Project::factory()->create([
            // Note: To ensure deterministic order
            'id' => '130bdf66-d370-4564-aec7-7171e9b415f7',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create([
            'description' => 'Test',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create([
            'description' => '',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create([
            'description' => 'Test',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create([
            'description' => 'Test',
        ]);
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Project,
            TimeEntryAggregationType::Description,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            Carbon::now()->subDays(2)->utc(),
            Carbon::now()->subDay()->utc(),
            true,
            null,
            null
        );

        // Assert
        $this->assertSame([
            'seconds' => 40,
            'cost' => 0,
            'grouped_type' => 'project',
            'grouped_data' => [
                [
                    'key' => $project2->getKey(),
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'description',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => 'Test',
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $project1->getKey(),
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'description',
                    'grouped_data' => [
                        [
                            'key' => 'Test',
                            'seconds' => 20,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_entries_without_billable_amounts(): void
    {
        // Arrange
        $project1 = Project::factory()->create([
            // Note: To ensure deterministic order
            'id' => '5de4e6df-9560-4675-95be-18d42c441bfc',
        ]);
        $project2 = Project::factory()->create([
            // Note: To ensure deterministic order
            'id' => '130bdf66-d370-4564-aec7-7171e9b415f7',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create([
            'description' => 'Test',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create([
            'description' => '',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create([
            'description' => 'Test',
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create([
            'description' => 'Test',
        ]);
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Project,
            TimeEntryAggregationType::Description,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            Carbon::now()->subDays(2)->utc(),
            Carbon::now()->subDay()->utc(),
            false,
            null,
            null
        );

        // Assert
        $this->assertSame([
            'seconds' => 40,
            'cost' => null,
            'grouped_type' => 'project',
            'grouped_data' => [
                [
                    'key' => $project2->getKey(),
                    'seconds' => 20,
                    'cost' => null,
                    'grouped_type' => 'description',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 10,
                            'cost' => null,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => 'Test',
                            'seconds' => 10,
                            'cost' => null,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $project1->getKey(),
                    'seconds' => 20,
                    'cost' => null,
                    'grouped_type' => 'description',
                    'grouped_data' => [
                        [
                            'key' => 'Test',
                            'seconds' => 20,
                            'cost' => null,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_entries_empty_state_by_day_and_project_with_filled_gaps(): void
    {
        // Arrange
        $timezone = 'Europe/Vienna';
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Day,
            TimeEntryAggregationType::Project,
            $timezone,
            Weekday::Monday,
            true,
            Carbon::now()->subDays(2)->utc(),
            Carbon::now()->subDay()->utc(),
            true,
            null,
            null
        );

        // Assert
        $this->assertSame([
            'seconds' => 0,
            'cost' => 0,
            'grouped_type' => 'day',
            'grouped_data' => [
                [
                    'key' => Carbon::now()->subDays(2)->timezone($timezone)->format('Y-m-d'),
                    'seconds' => 0,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [],
                ],
                [
                    'key' => Carbon::now()->subDay()->timezone($timezone)->format('Y-m-d'),
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
            Carbon::now()->subDay(),
            true,
            null,
            null
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
            Carbon::now()->subDay(),
            true,
            null,
            null
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
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project3)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->create();
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
            null,
            true,
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

    public function test_aggregate_time_can_round_up_per_time_entry(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 450)
            ->forProject($project1)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 449)
            ->forProject($project1)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 451)
            ->forProject($project2)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 450)
            ->forProject($project3)
            ->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 449)
            ->create();
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
            null,
            true,
            TimeEntryRoundingType::Up,
            15
        );

        // Assert
        $this->assertEqualsCanonicalizing([
            'seconds' => 4500,
            'cost' => 0,
            'grouped_type' => 'client',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 1800,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $project3->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client1->getKey(),
                    'seconds' => 1800,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project1->getKey(),
                            'seconds' => 1800,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client2->getKey(),
                    'seconds' => 900,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project2->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_can_round_down_per_time_entry(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 450)
            ->forProject($project1)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 449)
            ->forProject($project1)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 451)
            ->forProject($project2)->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 900 + 450)
            ->forProject($project3)
            ->create();
        TimeEntry::factory()->endWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:01'), 900 + 449)
            ->create();
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
            null,
            true,
            TimeEntryRoundingType::Down,
            15
        );

        // Assert
        $this->assertEqualsCanonicalizing([
            'seconds' => 1800,
            'cost' => 0,
            'grouped_type' => 'client',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 1800,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $project3->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client1->getKey(),
                    'seconds' => 0,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project1->getKey(),
                            'seconds' => 0,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client2->getKey(),
                    'seconds' => 0,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project2->getKey(),
                            'seconds' => 0,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_can_round_to_nearest_per_time_entry(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        TimeEntry::factory()->startWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00'), 449)
            ->forProject($project1)->create();
        TimeEntry::factory()->startWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00'), 450)
            ->forProject($project1)->create();
        TimeEntry::factory()->startWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00'), 450)
            ->forProject($project2)->create();
        TimeEntry::factory()->startWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00'), 450)
            ->forProject($project3)
            ->create();
        TimeEntry::factory()->startWithDuration(Carbon::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00'), 450)
            ->create();
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
            null,
            true,
            TimeEntryRoundingType::Nearest,
            15
        );

        // Assert
        $this->assertEqualsCanonicalizing([
            'seconds' => 3600,
            'cost' => 0,
            'grouped_type' => 'client',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 1800,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => null,
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $project3->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client1->getKey(),
                    'seconds' => 900,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project1->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                [
                    'key' => $client2->getKey(),
                    'seconds' => 900,
                    'cost' => 0,
                    'grouped_type' => 'project',
                    'grouped_data' => [
                        [
                            'key' => $project2->getKey(),
                            'seconds' => 900,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    // TODO: test with 1 minute

    public function test_aggregate_time_entries_by_client_and_project_with_filled_gaps(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project3)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->create();
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Client,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            true,
            null,
            null,
            true,
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

    public function test_aggregated_time_entries_with_descriptions_by_description_and_billable(): void
    {
        // Arrange
        TimeEntry::factory()->startWithDuration(now(), 10)->create([
            'description' => 'TEST 1',
            'billable' => true,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->create([
            'description' => '',
            'billable' => false,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->create([
            'description' => 'TEST 1',
            'billable' => false,
        ]);
        TimeEntry::factory()->startWithDuration(now(), 10)->create([
            'description' => '',
            'billable' => false,
        ]);
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntriesWithDescriptions(
            $query,
            TimeEntryAggregationType::Description,
            TimeEntryAggregationType::Billable,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null,
            true,
            null,
            null,
        );

        // Assert
        $this->assertSame([
            'seconds' => 40,
            'cost' => 0,
            'grouped_type' => 'description',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'billable',
                    'grouped_data' => [
                        [
                            'key' => '0',
                            'seconds' => 20,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                            'description' => 'Non-billable',
                            'color' => null,
                        ],
                    ],
                    'description' => null,
                    'color' => null,
                ],
                [
                    'key' => 'TEST 1',
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'billable',
                    'grouped_data' => [
                        [
                            'key' => '0',
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                            'description' => 'Non-billable',
                            'color' => null,
                        ],
                        [
                            'key' => '1',
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                            'description' => 'Billable',
                            'color' => null,
                        ],
                    ],
                    'description' => 'TEST 1',
                    'color' => null,
                ],
            ],
        ], $result);
    }

    public function test_aggregated_time_entries_with_descriptions_by_client_and_project(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $project1 = Project::factory()->forClient($client1)->create();
        $project2 = Project::factory()->forClient($client2)->create();
        $project3 = Project::factory()->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project1)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project2)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->forProject($project3)->create();
        TimeEntry::factory()->startWithDuration(now(), 10)->create();
        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntriesWithDescriptions(
            $query,
            TimeEntryAggregationType::Client,
            TimeEntryAggregationType::Project,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null,
            true,
            null,
            null,
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
                            'description' => null,
                            'color' => null,
                        ],
                        [
                            'key' => $project3->getKey(),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                            'description' => $project3->name,
                            'color' => $project3->color,
                        ],
                    ],
                    'description' => null,
                    'color' => null,
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
                            'description' => $project1->name,
                            'color' => $project1->color,
                        ],
                    ],
                    'description' => $client1->name,
                    'color' => null,
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
                            'description' => $project2->name,
                            'color' => $project2->color,
                        ],
                    ],
                    'description' => $client2->name,
                    'color' => null,
                ],
            ],
        ], $result);
    }

    public function test_aggregate_time_entries_group_by_tag_includes_no_tag_and_avoids_double_counting_overall(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $start = Carbon::now();

        // One entry with two tags (100s)
        TimeEntry::factory()->startWithDuration($start, 100)->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
        ]);
        // One entry with one tag (50s)
        TimeEntry::factory()->startWithDuration($start, 50)->create([
            'tags' => [$tag1->getKey()],
        ]);
        // One entry with no tags (25s)
        TimeEntry::factory()->startWithDuration($start, 25)->create([
            'tags' => [],
        ]);

        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Tag,
            null,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null,
            true,
            null,
            null
        );

        // Assert - overall total should be 175 and groups: null=25, tag1=150, tag2=100
        $expected = [
            'seconds' => 175,
            'cost' => 0,
            'grouped_type' => 'tag',
            'grouped_data' => [
                [
                    'key' => null,
                    'seconds' => 25,
                    'cost' => 0,
                    'grouped_type' => null,
                    'grouped_data' => null,
                ],
                [
                    'key' => $tag1->getKey(),
                    'seconds' => 150,
                    'cost' => 0,
                    'grouped_type' => null,
                    'grouped_data' => null,
                ],
                [
                    'key' => $tag2->getKey(),
                    'seconds' => 100,
                    'cost' => 0,
                    'grouped_type' => null,
                    'grouped_data' => null,
                ],
            ],
        ];
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_aggregate_time_entries_group_by_project_and_subgroup_tag(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $start = Carbon::now();

        TimeEntry::factory()->startWithDuration($start, 120)->forProject($project)->create([
            'tags' => [$tag1->getKey()],
        ]);
        TimeEntry::factory()->startWithDuration($start, 60)->forProject($project)->create([
            'tags' => [$tag2->getKey()],
        ]);

        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Project,
            TimeEntryAggregationType::Tag,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null,
            true,
            null,
            null
        );

        // Assert
        $expected = [
            'seconds' => 180,
            'cost' => 0,
            'grouped_type' => 'project',
            'grouped_data' => [
                [
                    'key' => $project->getKey(),
                    'seconds' => 180,
                    'cost' => 0,
                    'grouped_type' => 'tag',
                    'grouped_data' => [
                        [
                            'key' => $tag1->getKey(),
                            'seconds' => 120,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $tag2->getKey(),
                            'seconds' => 60,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_aggregate_time_entries_group_by_project_and_subgroup_tag_avoids_double_counting(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $start = Carbon::now();

        // One entry with two tags => subgroup rows show both tags, but project total should equal entry duration
        TimeEntry::factory()->startWithDuration($start, 100)->forProject($project)->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
        ]);

        $query = TimeEntry::query();

        // Act
        $result = $this->service->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Project,
            TimeEntryAggregationType::Tag,
            'Europe/Vienna',
            Weekday::Monday,
            false,
            null,
            null,
            true,
            null,
            null
        );

        // Assert
        $expected = [
            'seconds' => 100,
            'cost' => 0,
            'grouped_type' => 'project',
            'grouped_data' => [
                [
                    'key' => $project->getKey(),
                    'seconds' => 100,
                    'cost' => 0,
                    'grouped_type' => 'tag',
                    'grouped_data' => [
                        [
                            'key' => $tag1->getKey(),
                            'seconds' => 100,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        [
                            'key' => $tag2->getKey(),
                            'seconds' => 100,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEqualsCanonicalizing($expected, $result);
    }
}
