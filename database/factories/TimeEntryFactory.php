<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 year', '-1 hour');

        return [
            'description' => $this->faker->sentence(),
            'start' => $start,
            'end' => $this->faker->dateTimeBetween($start, 'now'),
            'billable' => $this->faker->boolean(),
            'tags' => [],
            'user_id' => User::factory(),
            'member_id' => Member::factory(),
            'task_id' => null,
            'project_id' => null,
            'organization_id' => Organization::factory(),
        ];
    }

    public function withTask(Organization $organization): self
    {
        return $this->state(function (array $attributes) use (&$organization): array {
            $project = Project::factory()->forOrganization($organization)->create();
            $task = Task::factory()->forProject($project)->forOrganization($organization)->create();

            return [
                'task_id' => $task->getKey(),
                'project_id' => $task->project->getKey(),
            ];
        });
    }

    public function withTags(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization): array {
            return [
                'tags' => [
                    Tag::factory()->forOrganization($organization)->create()->getKey(),
                    Tag::factory()->forOrganization($organization)->create()->getKey(),
                ],
            ];
        });
    }

    public function startBetween(Carbon $rangeStart, Carbon $rangeEnd, bool $fixedValueForMultiple = false): self
    {
        $fixedStart = Carbon::instance($this->faker->dateTimeBetween($rangeStart, $rangeEnd));

        return $this->state(function (array $attributes) use ($rangeStart, $rangeEnd, $fixedStart, $fixedValueForMultiple): array {
            $start = $fixedValueForMultiple ? $fixedStart : Carbon::instance($this->faker->dateTimeBetween($rangeStart, $rangeEnd));

            return [
                'start' => $start->utc(),
                'end' => $this->faker->dateTimeBetween($start, 'now'),
            ];
        });
    }

    public function active(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'end' => null,
            ];
        });
    }

    /**
     * @deprecated Use forMember instead
     */
    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function forMember(Member $member): static
    {
        return $this->state(function (array $attributes) use ($member): array {
            return [
                'member_id' => $member->getKey(),
                'user_id' => $member->user_id,
                'organization_id' => $member->organization_id,
            ];
        });
    }

    public function billable(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'billable' => true,
            ];
        });
    }

    public function startWithDuration(Carbon $start, int $durationInSeconds): self
    {
        return $this->state(function (array $attributes) use ($start, $durationInSeconds): array {
            return [
                'start' => $start->utc(),
                'end' => $start->copy()->addSeconds($durationInSeconds),
            ];
        });
    }

    public function start(Carbon $start): self
    {
        return $this->state(function (array $attributes) use ($start): array {
            return [
                'start' => $start->utc(),
            ];
        });
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization) {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function forProject(?Project $project): self
    {
        return $this->state(function (array $attributes) use ($project) {
            return [
                'project_id' => $project?->getKey(),
                'client_id' => $project?->client_id,
            ];
        });
    }

    public function forTask(?Task $task): self
    {
        return $this->state(function (array $attributes) use ($task) {
            return [
                'task_id' => $task?->getKey(),
                'project_id' => $task?->project?->getKey(),
                'client_id' => $task?->project?->client?->getKey(),
            ];
        });
    }
}
