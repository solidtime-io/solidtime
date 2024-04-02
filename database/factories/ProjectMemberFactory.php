<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMember>
 */
class ProjectMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billable_rate' => $this->faker->numberBetween(50, 1000) * 100,
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function forProject(Project $project): self
    {
        return $this->state(function (array $attributes) use ($project): array {
            return [
                'project_id' => $project->getKey(),
            ];
        });
    }
}
