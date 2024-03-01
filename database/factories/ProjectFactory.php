<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
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
            'organization_id' => Organization::factory(),
            'client_id' => null,
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization): array {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function withClient(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'client_id' => Client::factory(),
            ];
        });
    }

    public function forClient(?Client $client): self
    {
        return $this->state(function (array $attributes) use ($client): array {
            return [
                'client_id' => $client?->getKey(),
            ];
        });
    }
}
