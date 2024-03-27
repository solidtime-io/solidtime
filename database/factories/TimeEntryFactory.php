<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

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

    public function startBetween(Carbon $rangeStart, Carbon $rangeEnd): self
    {
        $start = Carbon::instance($this->faker->dateTimeBetween($rangeStart, $rangeEnd));

        return $this->state(function (array $attributes) use ($start): array {
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

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->getKey(),
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
            ];
        });
    }

    public function forTask(?Task $task): self
    {
        return $this->state(function (array $attributes) use ($task) {
            return [
                'task_id' => $task?->getKey(),
            ];
        });
    }
}
