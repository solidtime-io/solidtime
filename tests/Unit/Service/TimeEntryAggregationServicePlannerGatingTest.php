<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\TimeEntryAggregationService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryAggregationService::class)]
class TimeEntryAggregationServicePlannerGatingTest extends TestCaseWithDatabase
{
    public function test_grouping_phase_and_milestone_is_ignored_when_planner_disabled(): void
    {
        // Arrange
        config()->set('planner.enabled', false);
        $org = Organization::factory()->create();
        // Two entries with different milestone_ids
        TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'start' => Carbon::now()->subHour(),
            'end' => Carbon::now(),
            'milestone_id' => '11111111-1111-1111-1111-111111111111',
        ]);
        TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'start' => Carbon::now()->subHours(2),
            'end' => Carbon::now()->subHour(),
            'milestone_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $svc = app(TimeEntryAggregationService::class);
        $query = TimeEntry::query()->whereBelongsTo($org, 'organization');

        // Act: request grouping by Milestone when disabled
        $res = $svc->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Milestone,
            null,
            timezone: 'UTC',
            startOfWeek: Weekday::Monday,
            fillGapsInTimeGroups: false,
            start: null,
            end: null,
            showBillableRate: false,
            roundingType: null,
            roundingMinutes: null
        );

        // Assert: grouped_type is null (ignored), totals still computed
        $this->assertNull($res['grouped_type']);
        $this->assertArrayHasKey('seconds', $res);
        $this->assertArrayHasKey('grouped_data', $res);
        $this->assertNull($res['grouped_data']);
    }

    public function test_grouping_phase_and_milestone_is_applied_when_planner_enabled(): void
    {
        // Arrange
        config()->set('planner.enabled', true);
        $org = Organization::factory()->create();
        TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'start' => Carbon::now()->subHour(),
            'end' => Carbon::now(),
            'milestone_id' => '11111111-1111-1111-1111-111111111111',
        ]);
        TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'start' => Carbon::now()->subHours(2),
            'end' => Carbon::now()->subHour(),
            'milestone_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $svc = app(TimeEntryAggregationService::class);
        $query = TimeEntry::query()->whereBelongsTo($org, 'organization');

        // Act: grouping by Milestone when enabled
        $res = $svc->getAggregatedTimeEntries(
            $query,
            TimeEntryAggregationType::Milestone,
            null,
            timezone: 'UTC',
            startOfWeek: Weekday::Monday,
            fillGapsInTimeGroups: false,
            start: null,
            end: null,
            showBillableRate: false,
            roundingType: null,
            roundingMinutes: null
        );

        // Assert: grouped_type is milestone and grouped_data present
        $this->assertSame(TimeEntryAggregationType::Milestone->value, $res['grouped_type']);
        $this->assertIsArray($res['grouped_data']);
        $this->assertGreaterThanOrEqual(1, count($res['grouped_data']));
    }
}
