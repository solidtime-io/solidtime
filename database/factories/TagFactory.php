<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'organization_id' => Organization::factory(),
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization) {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function randomCreatedAt(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'created_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            ];
        });
    }
}
