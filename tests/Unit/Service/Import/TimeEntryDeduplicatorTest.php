<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import;

use App\Enums\TimeEntryType;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Service\Import\TimeEntryDeduplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(TimeEntryDeduplicator::class)]
class TimeEntryDeduplicatorTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->member = Member::factory()->forOrganization($this->organization)->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTimeEntry(string $start = '2024-03-04 09:00:00', int $duration = 3600, array $attributes = []): TimeEntry
    {
        return TimeEntry::factory()
            ->forMember($this->member)
            ->startWithDuration(Carbon::parse($start, 'UTC'), $duration)
            ->make(array_merge(['description' => 'Working hard'], $attributes));
    }

    public function test_claims_existing_equal_time_entry_only_once(): void
    {
        // Arrange
        $this->makeTimeEntry()->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $first = $deduplicator->claimExisting($this->makeTimeEntry());
        $second = $deduplicator->claimExisting($this->makeTimeEntry());

        // Assert
        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_claims_each_existing_duplicate_once(): void
    {
        // Arrange
        $this->makeTimeEntry()->save();
        $this->makeTimeEntry()->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $results = [
            $deduplicator->claimExisting($this->makeTimeEntry()),
            $deduplicator->claimExisting($this->makeTimeEntry()),
            $deduplicator->claimExisting($this->makeTimeEntry()),
        ];

        // Assert
        $this->assertSame([true, true, false], $results);
    }

    public function test_does_not_count_time_entries_created_after_the_month_was_loaded(): void
    {
        // Arrange
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $first = $deduplicator->claimExisting($this->makeTimeEntry());
        $this->makeTimeEntry()->save();
        $second = $deduplicator->claimExisting($this->makeTimeEntry());

        // Assert
        $this->assertFalse($first);
        $this->assertFalse($second);
    }

    public function test_does_not_claim_time_entry_that_differs_in_one_compared_field(): void
    {
        // Arrange
        $project = Project::factory()->forOrganization($this->organization)->create();
        $otherMember = Member::factory()->forOrganization($this->organization)->create();
        $this->makeTimeEntry()->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $results = [
            'description' => $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['description' => 'Other'])),
            'start' => $deduplicator->claimExisting($this->makeTimeEntry('2024-03-04 09:00:01', 3599)),
            'end' => $deduplicator->claimExisting($this->makeTimeEntry(duration: 3601)),
            'project' => $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['project_id' => $project->getKey()])),
            'member' => $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['member_id' => $otherMember->getKey()])),
            'type' => $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['type' => TimeEntryType::Break])),
        ];

        // Assert
        $this->assertSame([
            'description' => false,
            'start' => false,
            'end' => false,
            'project' => false,
            'member' => false,
            'type' => false,
        ], $results);
    }

    public function test_ignores_fields_that_can_change_after_an_export(): void
    {
        // Arrange
        $this->makeTimeEntry(attributes: ['billable' => false, 'tags' => []])->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $result = $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['billable' => true, 'tags' => ['tag']]));

        // Assert
        $this->assertTrue($result);
    }

    public function test_does_not_claim_time_entry_of_other_organization(): void
    {
        // Arrange
        $otherOrganization = Organization::factory()->create();
        $this->makeTimeEntry(attributes: ['organization_id' => $otherOrganization->getKey()])->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $result = $deduplicator->claimExisting($this->makeTimeEntry());

        // Assert
        $this->assertFalse($result);
    }

    public function test_matches_time_entries_at_month_boundaries(): void
    {
        // Arrange
        $this->makeTimeEntry('2024-03-31 23:59:59')->save();
        $this->makeTimeEntry('2024-04-01 00:00:00')->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $results = [
            $deduplicator->claimExisting($this->makeTimeEntry('2024-04-01 00:00:00')),
            $deduplicator->claimExisting($this->makeTimeEntry('2024-03-31 23:59:59')),
            $deduplicator->claimExisting($this->makeTimeEntry('2024-04-01 00:00:00')),
        ];

        // Assert
        $this->assertSame([true, true, false], $results);
    }

    public function test_never_claims_running_time_entry(): void
    {
        // Arrange
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());
        $timeEntry = $this->makeTimeEntry();
        $timeEntry->end = null;

        // Act
        $result = $deduplicator->claimExisting($timeEntry);

        // Assert
        $this->assertFalse($result);
    }

    public function test_loads_each_member_month_only_once(): void
    {
        // Arrange
        $otherMember = Member::factory()->forOrganization($this->organization)->create();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        DB::enableQueryLog();
        DB::flushQueryLog();
        $deduplicator->claimExisting($this->makeTimeEntry('2024-03-04 09:00:00'));
        $deduplicator->claimExisting($this->makeTimeEntry('2024-03-20 09:00:00'));
        $deduplicator->claimExisting($this->makeTimeEntry('2024-04-01 09:00:00'));
        $deduplicator->claimExisting($this->makeTimeEntry('2024-03-05 09:00:00'));
        $deduplicator->claimExisting($this->makeTimeEntry('2024-03-06 09:00:00', attributes: ['member_id' => $otherMember->getKey()]));
        $deduplicator->claimExisting($this->makeTimeEntry('2024-03-07 09:00:00', attributes: ['member_id' => $otherMember->getKey()]));
        $queryLog = DB::getQueryLog();

        // Assert
        $this->assertCount(3, $queryLog);
    }

    public function test_only_loads_time_entries_of_the_member(): void
    {
        // Arrange
        $otherMember = Member::factory()->forOrganization($this->organization)->create();
        $this->makeTimeEntry(attributes: ['member_id' => $otherMember->getKey()])->save();
        $deduplicator = new TimeEntryDeduplicator($this->organization->getKey());

        // Act
        $results = [
            $deduplicator->claimExisting($this->makeTimeEntry()),
            $deduplicator->claimExisting($this->makeTimeEntry(attributes: ['member_id' => $otherMember->getKey()])),
        ];

        // Assert
        $this->assertSame([false, true], $results);
    }
}
