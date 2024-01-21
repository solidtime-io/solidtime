<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
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
            'organization_id' => Team::factory(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function forOrganization(Team $organization): self
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
