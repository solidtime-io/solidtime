<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'project_id' => Project::factory(),
            'organization_id' => Organization::factory(),
            'done_at' => null,
            'estimated_time' => null,
        ];
    }

    public function forProject(Project $project): self
    {
        return $this->state(function (array $attributes) use ($project) {
            return [
                'project_id' => $project->getKey(),
            ];
        });
    }

    public function isDone(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'done_at' => $this->faker->dateTime('now', 'UTC'),
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
}
