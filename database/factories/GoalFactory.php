<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalType;
use App\Enums\TimeEntryType;
use App\Enums\Weekday;
use App\Models\Goal;
use App\Models\Member;
use App\Models\Organization;
use App\Service\Dto\GoalFiltersDto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filters = new GoalFiltersDto;
        $filters->timeEntryType = TimeEntryType::Work;

        return [
            'name' => $this->faker->sentence(3),
            'type' => GoalType::Personal,
            'comparison' => $this->faker->randomElement(GoalComparison::cases()),
            'target_seconds' => $this->faker->numberBetween(1, 40) * 3600,
            'period' => $this->faker->randomElement(GoalPeriod::cases()),
            'filters' => $filters,
            'timezone' => 'UTC',
            'week_start' => Weekday::Monday,
            'archived_at' => null,
            'organization_id' => Organization::factory(),
            // The member has to belong to the organization of the goal
            'member_id' => fn (array $attributes) => Member::factory()->state([
                'organization_id' => $attributes['organization_id'],
            ]),
        ];
    }

    /**
     * Goal in the given organization, for a new member of that organization unless forMember() is used.
     */
    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->getKey(),
            'member_id' => Member::factory()->forOrganization($organization),
        ]);
    }

    /**
     * Personal goal of the given member.
     */
    public function forMember(Member $member): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GoalType::Personal,
            'member_id' => $member->getKey(),
            'organization_id' => $member->organization_id,
        ]);
    }

    /**
     * Organization goal that counts the time of the given member.
     */
    public function organizationGoalForMember(Member $member): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GoalType::Organization,
            'member_id' => $member->getKey(),
            'organization_id' => $member->organization_id,
        ]);
    }

    /**
     * Organization goal that counts the time of every member.
     */
    public function forEveryMember(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GoalType::Organization,
            'member_id' => null,
        ]);
    }

    public function type(GoalType $type): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function timezone(string $timezone): self
    {
        return $this->state(fn (array $attributes): array => [
            'timezone' => $timezone,
        ]);
    }

    public function weekStart(Weekday $weekStart): self
    {
        return $this->state(fn (array $attributes): array => [
            'week_start' => $weekStart,
        ]);
    }

    public function atLeast(int $targetSeconds): self
    {
        return $this->state(fn (array $attributes): array => [
            'comparison' => GoalComparison::AtLeast,
            'target_seconds' => $targetSeconds,
        ]);
    }

    public function lessThan(int $targetSeconds): self
    {
        return $this->state(fn (array $attributes): array => [
            'comparison' => GoalComparison::LessThan,
            'target_seconds' => $targetSeconds,
        ]);
    }

    public function period(GoalPeriod $period): self
    {
        return $this->state(fn (array $attributes): array => [
            'period' => $period,
        ]);
    }

    public function filters(GoalFiltersDto $filters): self
    {
        return $this->state(fn (array $attributes): array => [
            'filters' => $filters,
        ]);
    }

    public function randomCreatedAt(): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
