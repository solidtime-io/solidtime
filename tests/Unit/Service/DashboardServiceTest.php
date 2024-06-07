<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Enums\Weekday;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(DashboardService::class)]
#[UsesClass(DashboardService::class)]
class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;

    public function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    public function test_daily_tracked_hours_returns_correct_values(): void
    {
        // Arrange
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        $timeEntry2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->getDailyTrackedHours($user, $organization, 5);

        // Assert
        $this->assertSame([
            [
                'date' => '2023-12-28',
                'duration' => 0,
            ],
            [
                'date' => '2023-12-29',
                'duration' => 0,
            ],
            [
                'date' => '2023-12-30',
                'duration' => 40,
            ],
            [
                'date' => '2023-12-31',
                'duration' => 40,
            ],
            [
                'date' => '2024-01-01',
                'duration' => 0,
            ],
        ], $result);
    }

    public function test_weekly_history_returns_correct_values(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        // Note: This is a Sunday
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        // Note: This is a Saturday
        $timeEntry2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->getWeeklyHistory($user, $organization);

        // Assert
        $this->assertSame([
            [
                'date' => '2023-12-31',
                'duration' => 40,
            ],
            [
                'date' => '2024-01-01',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-02',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-03',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-04',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-05',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-06',
                'duration' => 0,
            ],
        ], $result);
    }

    public function test_total_weekly_time_returns_correct_value(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        // Note: This is a Sunday
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        // Note: This is a Saturday
        $timeEntry2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->totalWeeklyTime($user, $organization);

        // Assert
        $this->assertSame(40, $result);
    }

    public function test_total_weekly_billable_time_returns_correct_value(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        // Note: This is a Sunday
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'billable' => true,
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        // Note: This is a Sunday (non-billable)
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'billable' => false,
            'start' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 59, 'UTC'),
        ]);
        // Note: This is a Saturday
        $timeEntry2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'billable' => true,
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->totalWeeklyBillableTime($user, $organization);

        // Assert
        $this->assertSame(40, $result);
    }

    public function test_total_weekly_billable_amount_returns_correct_value(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $currency = 'USD';
        $organization = Organization::factory()->create([
            'currency' => $currency,
        ]);
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        // Note: This is a Sunday
        $timeEntry1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'billable' => true,
            'billable_rate' => 50 * 100,
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 31, 0, 0, 0, 'UTC'),
        ]);
        // Note: This is a Sunday (non-billable)
        $timeEntry2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'billable' => false,
            'start' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 59, 'UTC'),
        ]);
        // Note: This is a Saturday
        $timeEntry3 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'billable' => true,
            'billable_rate' => 100 * 100,
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->totalWeeklyBillableAmount($user, $organization);

        // Assert
        $this->assertSame([
            'value' => 5000,
            'currency' => $currency,
        ], $result);
    }

    public function test_weekly_project_overview_returns_correct_value_if_time_entries_for_projects_exist_in_current_week(): void
    {
        // Arrange
        // Note: Is a Monday
        $now = Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna')->toImmutable();
        $this->travelTo($now);
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $organization = Organization::factory()->withOwner($user)->create();
        $member = Member::factory()->forUser($user)->forOrganization($organization)->role(Role::Owner)->create();
        $project1 = Project::factory()->forOrganization($organization)->create();
        $project2 = Project::factory()->forOrganization($organization)->create();
        $timeEntry1Project1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($project1)->create([
            // Note: At the start of the week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry2Project1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($project1)->create([
            // Note: At the end of the week
            'start' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->utc(),
            'end' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry1Project2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($project2)->create([
            // Note: At the start of the week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry2Project2 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($project2)->create([
            // Note: At the end of the week
            'start' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->utc(),
            'end' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry1WithoutProject = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: At the start of the week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry2WithoutProject = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: At the end of the week
            'start' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->utc(),
            'end' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry1WithoutProjectOutsideOfWeek = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: Outside of week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->subSecond()->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(39)->utc(),
        ]);

        // Act
        $result = $this->dashboardService->weeklyProjectOverview($user, $organization);

        // Assert
        $this->assertEqualsCanonicalizing([
            [
                'value' => 80,
                'id' => $project1->getKey(),
                'name' => $project1->name,
                'color' => $project1->color,
            ],
            [
                'value' => 80,
                'id' => $project2->getKey(),
                'name' => $project2->name,
                'color' => $project2->color,
            ],
            [
                'value' => 80,
                'id' => null,
                'name' => 'No project',
                'color' => '#cccccc',
            ],
        ], $result);
    }

    public function test_weekly_project_overview_returns_correct_value_if_only_entries_without_project_exist_in_the_week(): void
    {
        // Arrange
        // Note: Is a Monday
        $now = Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna')->toImmutable();
        $this->travelTo($now);
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        $timeEntry1WithoutProject = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: At the start of the week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry2WithoutProject = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: At the end of the week
            'start' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->utc(),
            'end' => $now->endOfWeek($user->week_start->toEndOfWeek()->carbonWeekDay())->addSeconds(40)->utc(),
        ]);
        $timeEntry1WithoutProjectOutsideOfWeek = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            // Note: Outside of week
            'start' => $now->startOfWeek($user->week_start->carbonWeekDay())->subSecond()->utc(),
            'end' => $now->startOfWeek($user->week_start->carbonWeekDay())->addSeconds(39)->utc(),
        ]);

        // Act
        $result = $this->dashboardService->weeklyProjectOverview($user, $organization);

        // Assert
        $this->assertSame([
            [
                'value' => 80,
                'id' => null,
                'name' => 'No project',
                'color' => '#cccccc',
            ],
        ], $result);
    }

    public function test_weekly_project_overview_returns_correct_value_if_no_entries_are_in_the_week(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();

        // Act
        $result = $this->dashboardService->weeklyProjectOverview($user, $organization);

        // Assert
        $this->assertSame([
            [
                'value' => 0,
                'id' => null,
                'name' => 'No project',
                'color' => '#cccccc',
            ],
        ], $result);
    }

    public function test_latest_team_activity_returns_the_most_current_working_users_and_what_they_are_working_on(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member1 = Member::factory()->forOrganization($organization)->create();
        $member2 = Member::factory()->forOrganization($organization)->create();
        $member3 = Member::factory()->forOrganization($organization)->create();
        $member4 = Member::factory()->forOrganization($organization)->create();
        $member5 = Member::factory()->forOrganization($organization)->create();
        $task1 = Task::factory()->forOrganization($organization)->create();
        $timeEntry1 = TimeEntry::factory()->forMember($member1)->forOrganization($organization)->active()->create([
            'start' => now()->subMinutes(10),
        ]);
        $timeEntry2 = TimeEntry::factory()->forMember($member2)->forOrganization($organization)->create([
            'start' => now()->subMinutes(20),
        ]);
        $timeEntry3 = TimeEntry::factory()->forMember($member3)->forOrganization($organization)->forTask($task1)->create([
            'description' => '',
            'start' => now()->subMinutes(30),
        ]);
        $timeEntry4 = TimeEntry::factory()->forMember($member4)->forOrganization($organization)->forTask($task1)->create([
            'description' => 'TEST 123',
            'start' => now()->subMinutes(40),
        ]);
        $timeEntry5 = TimeEntry::factory()->forMember($member4)->forOrganization($organization)->forTask($task1)->create([
            'description' => 'TEST 321',
            'start' => now()->subMinutes(50),
        ]);
        $timeEntry6 = TimeEntry::factory()->forMember($member5)->forOrganization($organization)->forTask($task1)->create([
            'description' => 'TEST 321',
            'start' => now()->subMinutes(60),
        ]);

        // Act
        $result = $this->dashboardService->latestTeamActivity($organization);

        // Assert
        $this->assertSame([
            [
                'member_id' => $member1->getKey(),
                'name' => $member1->user->name,
                'description' => $timeEntry1->description,
                'time_entry_id' => $timeEntry1->getKey(),
                'task_id' => null,
                'status' => true,
            ],
            [
                'member_id' => $member2->getKey(),
                'name' => $member2->user->name,
                'description' => $timeEntry2->description,
                'time_entry_id' => $timeEntry2->getKey(),
                'task_id' => null,
                'status' => false,
            ],
            [
                'member_id' => $member3->getKey(),
                'name' => $member3->user->name,
                'description' => $timeEntry3->description,
                'time_entry_id' => $timeEntry3->getKey(),
                'task_id' => $task1->getKey(),
                'status' => false,
            ],
            [
                'member_id' => $member4->getKey(),
                'name' => $member4->user->name,
                'description' => $timeEntry4->description,
                'time_entry_id' => $timeEntry4->getKey(),
                'task_id' => $task1->getKey(),
                'status' => false,
            ],
        ], $result);
    }

    public function test_latest_tasks_returns_the_4_tasks_with_the_latest_time_entries(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        $task1 = Task::factory()->forOrganization($organization)->create();
        $task2 = Task::factory()->forOrganization($organization)->create();
        $task3 = Task::factory()->forOrganization($organization)->create();
        $task4 = Task::factory()->forOrganization($organization)->create();
        $task5 = Task::factory()->forOrganization($organization)->create();

        $timeEntry1Task1 = TimeEntry::factory()->forTask($task1)->forMember($member)->forOrganization($organization)->create([
            'start' => now()->subMinutes(20),
        ]);
        $timeEntry1Task2 = TimeEntry::factory()->forTask($task2)->forMember($member)->forOrganization($organization)->create([
            'start' => now()->subMinutes(30),
        ]);
        $timeEntry1Task3 = TimeEntry::factory()->forTask($task3)->forMember($member)->forOrganization($organization)->create([
            'start' => now()->subMinutes(40),
        ]);
        $timeEntry1Task4 = TimeEntry::factory()->forTask($task4)->forMember($member)->forOrganization($organization)->create([
            'start' => now()->subMinutes(50),
        ]);
        $timeEntry1Task5 = TimeEntry::factory()->forTask($task5)->forMember($member)->forOrganization($organization)->create([
            'start' => now()->subMinutes(60),
        ]);

        // Act
        $result = $this->dashboardService->latestTasks($user, $organization);

        // Assert
        $this->assertSame([
            [
                'id' => $timeEntry1Task1->task->getKey(),
                'name' => $timeEntry1Task1->task->name,
                'project_name' => $timeEntry1Task1->task->project->name,
                'project_id' => $timeEntry1Task1->task->project->getKey(),
            ],
            [
                'id' => $timeEntry1Task2->task->getKey(),
                'name' => $timeEntry1Task2->task->name,
                'project_name' => $timeEntry1Task2->task->project->name,
                'project_id' => $timeEntry1Task2->task->project->getKey(),
            ],
            [
                'id' => $timeEntry1Task3->task->getKey(),
                'name' => $timeEntry1Task3->task->name,
                'project_name' => $timeEntry1Task3->task->project->name,
                'project_id' => $timeEntry1Task3->task->project->getKey(),
            ],
            [
                'id' => $timeEntry1Task4->task->getKey(),
                'name' => $timeEntry1Task4->task->name,
                'project_name' => $timeEntry1Task4->task->project->name,
                'project_id' => $timeEntry1Task4->task->project->getKey(),
            ],
        ], $result);
    }

    public function test_last_seven_days_returns_spend_time_in_the_last_seven_days_aggregated_in_three_hour_blocks(): void
    {
        // Arrange
        $now = Carbon::create(2024, 4, 17, 12, 0, 0, 'Europe/Vienna')->utc();
        $this->travelTo($now);
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();
        $timeEntryOverWholePeriod = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'start' => now('Europe/Vienna')->subDays(7)->startOfDay()->utc(),
            'end' => now('Europe/Vienna')->endOfDay()->addSecond()->utc(), // TODO: fix problem with last second
        ]);
        $timeEntryOverWholePeriodWithoutEnd = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'start' => now('Europe/Vienna')->subDays(7)->startOfDay()->utc(),
            'end' => null,
        ]);
        $timeEntry1Task1 = TimeEntry::factory()->forMember($member)->forOrganization($organization)->create([
            'start' => now('Europe/Vienna')->subMinutes(30)->utc(),
            'end' => now('Europe/Vienna')->subMinutes(20)->utc(),
        ]);

        // Act
        $result = $this->dashboardService->lastSevenDays($user, $organization);

        // Assert
        $this->assertSame([
            0 => [
                'date' => '2024-04-17',
                'duration' => 130200,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 22200,
                    4 => 10800,
                    5 => 10800,
                    6 => 10800,
                    7 => 10800,
                ],
            ],
            1 => [
                'date' => '2024-04-16',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
            2 => [
                'date' => '2024-04-15',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
            3 => [
                'date' => '2024-04-14',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
            4 => [
                'date' => '2024-04-13',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
            5 => [
                'date' => '2024-04-12',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
            6 => [
                'date' => '2024-04-11',
                'duration' => 172800,
                'history' => [
                    0 => 21600,
                    1 => 21600,
                    2 => 21600,
                    3 => 21600,
                    4 => 21600,
                    5 => 21600,
                    6 => 21600,
                    7 => 21600,
                ],
            ],
        ], $result);
    }
}
