<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'color' => $this->faker->hexColor(),
            'organization_id' => Team::factory(),
            'client_id' => null,
        ];
    }

    public function forOrganization(Team $organization): self
    {
        return $this->state(function (array $attributes) use ($organization) {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function forClient(?Client $client): self
    {
        return $this->state(function (array $attributes) use ($client) {
            return [
                'client_id' => $client?->getKey(),
            ];
        });
    }
}
