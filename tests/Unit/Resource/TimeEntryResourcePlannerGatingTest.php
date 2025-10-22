<?php

declare(strict_types=1);

namespace Tests\Unit\Resource;

use App\Http\Resources\V1\TimeEntry\TimeEntryResource;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryResource::class)]
class TimeEntryResourcePlannerGatingTest extends TestCaseWithDatabase
{
    public function test_resource_hides_milestone_id_when_planner_disabled(): void
    {
        config()->set('planner.enabled', false);
        $org = Organization::factory()->create();
        $entry = TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'milestone_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $arr = (new TimeEntryResource($entry))->toArray(Request::create('/'));
        $this->assertArrayNotHasKey('milestone_id', $arr);
    }

    public function test_resource_shows_milestone_id_when_planner_enabled(): void
    {
        config()->set('planner.enabled', true);
        $org = Organization::factory()->create();
        $entry = TimeEntry::factory()->create([
            'organization_id' => $org->getKey(),
            'milestone_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $arr = (new TimeEntryResource($entry))->toArray(Request::create('/'));
        $this->assertArrayHasKey('milestone_id', $arr);
        $this->assertSame('22222222-2222-2222-2222-222222222222', $arr['milestone_id']);
    }
}
