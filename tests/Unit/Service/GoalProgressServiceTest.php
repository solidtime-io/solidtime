<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalStatus;
use App\Enums\TagMatchType;
use App\Enums\TimeEntryType;
use App\Enums\Weekday;
use App\Models\Client;
use App\Models\Goal;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Dto\GoalFiltersDto;
use App\Service\GoalProgressService;
use App\Service\TimeEntryFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(GoalProgressService::class)]
class GoalProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoalProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GoalProgressService::class);
    }

    private function progressAt(Goal $goal, Carbon $now): int
    {
        Carbon::setTestNow($now);

        return $this->service->getCurrentProgress($goal, $now)->trackedSeconds;
    }

    public function test_period_bounds_of_day_goal_are_calculated_in_the_timezone_of_the_goal(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $goal = Goal::factory()->forMember($member)->timezone('Europe/Vienna')->period(GoalPeriod::Day)->create();
        $date = Carbon::create(2024, 1, 1, 0, 30, 0, 'Europe/Vienna');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        $this->assertSame('2023-12-31T23:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-01-01T23:00:00Z', $end->toIso8601ZuluString());
        $this->assertSame('UTC', $start->getTimezone()->getName());
    }

    public function test_period_bounds_of_week_goal_respect_the_week_start_of_the_goal(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $goal = Goal::factory()->forMember($member)->weekStart(Weekday::Sunday)->period(GoalPeriod::Week)->create();
        // Wednesday
        $date = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        // Sunday before
        $this->assertSame('2023-12-31T00:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-01-07T00:00:00Z', $end->toIso8601ZuluString());
    }

    public function test_period_bounds_of_month_goal_cover_the_calendar_month(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $goal = Goal::factory()->forMember($member)->timezone('America/New_York')->period(GoalPeriod::Month)->create();
        $date = Carbon::create(2024, 2, 15, 12, 0, 0, 'America/New_York');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        $this->assertSame('2024-02-01T05:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-03-01T05:00:00Z', $end->toIso8601ZuluString());
    }

    public function test_period_bounds_use_the_settings_pinned_on_the_goal_not_the_settings_of_the_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['timezone' => 'America/New_York', 'week_start' => Weekday::Monday]);
        $member = Member::factory()->forUser($memberUser)->forOrganization($organization)->create();
        $goal = Goal::factory()->organizationGoalForMember($member)
            ->timezone('Europe/Vienna')
            ->weekStart(Weekday::Sunday)
            ->period(GoalPeriod::Week)
            ->create();
        // Wednesday
        $date = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        // Sunday 00:00 Vienna time
        $this->assertSame('2023-12-30T23:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-01-06T23:00:00Z', $end->toIso8601ZuluString());
    }

    public function test_period_bounds_of_week_goal_spanning_the_end_of_daylight_saving_time_start_and_end_at_local_midnight(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $goal = Goal::factory()->forMember($member)
            ->timezone('Europe/Vienna')
            ->weekStart(Weekday::Monday)
            ->period(GoalPeriod::Week)
            ->create();
        // Friday before the switch on Sunday 2024-10-27
        $date = Carbon::create(2024, 10, 25, 12, 0, 0, 'Europe/Vienna');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        // Monday 00:00 CEST (UTC+2) to Monday 00:00 CET (UTC+1)
        $this->assertSame('2024-10-20T22:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-10-27T23:00:00Z', $end->toIso8601ZuluString());
    }

    public function test_period_bounds_of_week_goal_use_the_local_weekday_when_it_differs_from_the_utc_weekday(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $goal = Goal::factory()->forMember($member)
            ->timezone('Europe/Vienna')
            ->weekStart(Weekday::Monday)
            ->period(GoalPeriod::Week)
            ->create();
        // Sunday 23:30 in UTC, but already Monday 00:30 in Vienna
        $date = Carbon::create(2024, 1, 7, 23, 30, 0, 'UTC');

        // Act
        [$start, $end] = $this->service->getPeriodBounds($goal, $date);

        // Assert
        // The new week that started on Monday 00:00 in Vienna, not the week of the UTC Sunday
        $this->assertSame('2024-01-07T23:00:00Z', $start->toIso8601ZuluString());
        $this->assertSame('2024-01-14T23:00:00Z', $end->toIso8601ZuluString());
    }

    public function test_progress_assigns_entries_around_local_midnight_to_the_week_their_start_is_in(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forMember($member)
            ->timezone('Europe/Vienna')
            ->weekStart(Weekday::Monday)
            ->period(GoalPeriod::Week)
            ->create();
        // Sunday 23:59 in Vienna, previous week
        TimeEntry::factory()->forMember($member)->startWithDuration(Carbon::create(2024, 1, 7, 22, 59, 0, 'UTC'), 600)->create();
        // Monday 00:00 in Vienna, first second of the week
        TimeEntry::factory()->forMember($member)->startWithDuration(Carbon::create(2024, 1, 7, 23, 0, 0, 'UTC'), 300)->create();

        // Act
        $progress = $this->progressAt($goal, Carbon::create(2024, 1, 8, 12, 0, 0, 'UTC'));

        // Assert
        $this->assertSame(300, $progress);
    }

    public function test_progress_does_not_count_entries_that_start_at_the_end_of_the_period(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forMember($member)->timezone('Europe/Vienna')->period(GoalPeriod::Day)->create();
        // 22:59:59 UTC is the last second of the day in Vienna
        TimeEntry::factory()->forMember($member)->startWithDuration(Carbon::create(2024, 1, 1, 22, 59, 59, 'UTC'), 600)->create();
        // 23:00 UTC is the first second of the next day in Vienna
        TimeEntry::factory()->forMember($member)->startWithDuration(Carbon::create(2024, 1, 1, 23, 0, 0, 'UTC'), 300)->create();

        // Act
        $progress = $this->progressAt($goal, Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));

        // Assert
        $this->assertSame(600, $progress);
    }

    public function test_progress_of_goal_for_a_member_only_counts_that_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $creator = Member::factory()->forOrganization($organization)->create();
        $memberA = Member::factory()->forOrganization($organization)->create();
        $memberB = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->organizationGoalForMember($memberA)->period(GoalPeriod::Day)->atLeast(3600)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($memberA)->startWithDuration($now->copy()->subHours(2), 1000)->create();
        TimeEntry::factory()->forMember($memberB)->startWithDuration($now->copy()->subHours(2), 2000)->create();
        TimeEntry::factory()->forMember($creator)->startWithDuration($now->copy()->subHours(2), 4000)->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(1000, $trackedSeconds);
    }

    public function test_progress_of_goal_for_every_member_counts_all_members_of_the_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $creator = Member::factory()->forOrganization($organization)->create();
        $memberOfOtherOrganization = Member::factory()->create();
        $memberA = Member::factory()->forOrganization($organization)->create();
        $memberB = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forOrganization($organization)->forEveryMember()->period(GoalPeriod::Day)->atLeast(3600)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($memberA)->startWithDuration($now->copy()->subHours(2), 1000)->create();
        TimeEntry::factory()->forMember($memberB)->startWithDuration($now->copy()->subHours(2), 2000)->create();
        TimeEntry::factory()->forMember($creator)->startWithDuration($now->copy()->subHours(2), 4000)->create();
        TimeEntry::factory()->forMember($memberOfOtherOrganization)->startWithDuration($now->copy()->subHours(2), 8000)->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(7000, $trackedSeconds);
    }

    public function test_progress_of_goal_for_every_member_can_be_narrowed_down_with_the_member_ids_filter(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $memberA = Member::factory()->forOrganization($organization)->create();
        $memberB = Member::factory()->forOrganization($organization)->create();
        $memberC = Member::factory()->forOrganization($organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setMemberIds([$memberA->getKey(), $memberB->getKey()]);
        $goal = Goal::factory()->forOrganization($organization)->forEveryMember()
            ->period(GoalPeriod::Day)->atLeast(3600)->filters($filters)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($memberA)->startWithDuration($now->copy()->subHours(2), 1000)->create();
        TimeEntry::factory()->forMember($memberB)->startWithDuration($now->copy()->subHours(2), 2000)->create();
        TimeEntry::factory()->forMember($memberC)->startWithDuration($now->copy()->subHours(2), 4000)->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(3000, $trackedSeconds);
    }

    public function test_progress_of_goal_for_one_member_ignores_the_member_ids_filter(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $otherMember = Member::factory()->forOrganization($organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setMemberIds([$otherMember->getKey()]);
        $goal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->atLeast(3600)->filters($filters)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($member)->startWithDuration($now->copy()->subHours(2), 1000)->create();
        TimeEntry::factory()->forMember($otherMember)->startWithDuration($now->copy()->subHours(2), 2000)->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(1000, $trackedSeconds);
    }

    public function test_progress_counts_running_time_entry_up_to_now(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->atLeast(3600)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->startWithDuration($now->copy()->subHours(3), 600)
            ->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->start($now->copy()->subMinutes(30))
            ->active()
            ->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(600 + 1800, $trackedSeconds);
    }

    public function test_progress_does_not_count_running_time_entry_that_started_before_the_period(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->atLeast(3600)->create();
        $now = Carbon::create(2024, 1, 3, 1, 0, 0, 'UTC');
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->start($now->copy()->subHours(3))
            ->active()
            ->create();

        // Act
        $trackedSeconds = $this->progressAt($goal, $now);

        // Assert
        $this->assertSame(0, $trackedSeconds);
    }

    public function test_progress_applies_project_task_client_and_none_filters(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $client = Client::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forOrganization($organization)->forClient($client)->create();
        $otherProject = Project::factory()->forOrganization($organization)->create();
        $task = Task::factory()->forOrganization($organization)->forProject($project)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        $start = $now->copy()->subHours(2);
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->forTask($task)
            ->startWithDuration($start, 100)->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($project)
            ->startWithDuration($start, 200)->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->forProject($otherProject)
            ->startWithDuration($start, 400)->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->startWithDuration($start, 800)->create();

        $projectFilters = new GoalFiltersDto;
        $projectFilters->setProjectIds([$project->getKey()]);
        $taskFilters = new GoalFiltersDto;
        $taskFilters->setTaskIds([$task->getKey()]);
        $clientFilters = new GoalFiltersDto;
        $clientFilters->setClientIds([$client->getKey()]);
        $noneProjectFilters = new GoalFiltersDto;
        $noneProjectFilters->setProjectIds([TimeEntryFilter::NONE_VALUE]);
        $noFilters = new GoalFiltersDto;

        $projectGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($projectFilters)->create();
        $taskGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($taskFilters)->create();
        $clientGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($clientFilters)->create();
        $noneProjectGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($noneProjectFilters)->create();
        $allGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($noFilters)->create();

        // Act & Assert
        $this->assertSame(300, $this->progressAt($projectGoal, $now));
        $this->assertSame(100, $this->progressAt($taskGoal, $now));
        $this->assertSame(300, $this->progressAt($clientGoal, $now));
        $this->assertSame(800, $this->progressAt($noneProjectGoal, $now));
        $this->assertSame(1500, $this->progressAt($allGoal, $now));
    }

    public function test_progress_applies_tag_filters_with_match_type(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $tag = Tag::factory()->forOrganization($organization)->create();
        $otherTag = Tag::factory()->forOrganization($organization)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        $start = $now->copy()->subHours(2);
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->startWithDuration($start, 100)->create(['tags' => [$tag->getKey()]]);
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->startWithDuration($start, 200)->create(['tags' => [$otherTag->getKey()]]);
        TimeEntry::factory()->forMember($member)->forOrganization($organization)
            ->startWithDuration($start, 400)->create(['tags' => []]);

        $containsFilters = new GoalFiltersDto;
        $containsFilters->setTagIds([$tag->getKey()]);
        $containsFilters->tagMatchType = TagMatchType::Contains;
        $notContainsFilters = new GoalFiltersDto;
        $notContainsFilters->setTagIds([$tag->getKey()]);
        $notContainsFilters->tagMatchType = TagMatchType::NotContains;
        $noneFilters = new GoalFiltersDto;
        $noneFilters->setTagIds([TimeEntryFilter::NONE_VALUE]);

        $containsGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($containsFilters)->create();
        $notContainsGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($notContainsFilters)->create();
        $noneGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($noneFilters)->create();

        // Act & Assert
        $this->assertSame(100, $this->progressAt($containsGoal, $now));
        $this->assertSame(600, $this->progressAt($notContainsGoal, $now));
        $this->assertSame(400, $this->progressAt($noneGoal, $now));
    }

    public function test_progress_applies_billable_and_time_entry_type_filters(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $now = Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC');
        $start = $now->copy()->subHours(2);
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->billable()
            ->startWithDuration($start, 100)->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->notBillable()
            ->startWithDuration($start, 200)->create();
        TimeEntry::factory()->forMember($member)->forOrganization($organization)->isBreak()
            ->startWithDuration($start, 400)->create();

        $billableFilters = new GoalFiltersDto;
        $billableFilters->billable = true;
        $nonBillableFilters = new GoalFiltersDto;
        $nonBillableFilters->billable = false;
        $workFilters = new GoalFiltersDto;
        $workFilters->timeEntryType = TimeEntryType::Work;
        $breakFilters = new GoalFiltersDto;
        $breakFilters->timeEntryType = TimeEntryType::Break;
        $allFilters = new GoalFiltersDto;

        $billableGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($billableFilters)->create();
        $nonBillableGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($nonBillableFilters)->create();
        $workGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($workFilters)->create();
        $breakGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($breakFilters)->create();
        $allGoal = Goal::factory()->forMember($member)->period(GoalPeriod::Day)->filters($allFilters)->create();

        // Act & Assert
        $this->assertSame(100, $this->progressAt($billableGoal, $now));
        $this->assertSame(600, $this->progressAt($nonBillableGoal, $now));
        $this->assertSame(300, $this->progressAt($workGoal, $now));
        $this->assertSame(400, $this->progressAt($breakGoal, $now));
        $this->assertSame(700, $this->progressAt($allGoal, $now));
    }

    public function test_status_of_at_least_goal_is_achieved_once_target_is_reached(): void
    {
        // Arrange
        $goal = new Goal;
        $goal->comparison = GoalComparison::AtLeast;
        $goal->target_seconds = 3600;

        // Act & Assert
        $this->assertSame(GoalStatus::InProgress, $this->service->getStatus($goal, 0));
        $this->assertSame(GoalStatus::InProgress, $this->service->getStatus($goal, 3599));
        $this->assertSame(GoalStatus::Achieved, $this->service->getStatus($goal, 3600));
    }

    public function test_status_of_less_than_goal_is_exceeded_once_target_is_reached(): void
    {
        // Arrange
        $goal = new Goal;
        $goal->comparison = GoalComparison::LessThan;
        $goal->target_seconds = 3600;

        // Act & Assert
        $this->assertSame(GoalStatus::OnTrack, $this->service->getStatus($goal, 0));
        $this->assertSame(GoalStatus::OnTrack, $this->service->getStatus($goal, 3599));
        $this->assertSame(GoalStatus::Exceeded, $this->service->getStatus($goal, 3600));
    }
}
