<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'currency' => $this->faker->currencyCode(),
            'billable_rate' => null,
            'user_id' => User::factory(),
            'personal_team' => true,
        ];
    }

    public function billableRate(?int $billableRate): self
    {
        return $this->state(fn (array $attributes) => [
            'billable_rate' => $billableRate,
        ]);
    }

    public function withBillableRate(): self
    {
        return $this->state(fn (array $attributes) => [
            'billable_rate' => $this->faker->numberBetween(50, 1000) * 100,
        ]);
    }

    public function withOwner(?User $owner = null): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner === null ? User::factory() : $owner->getKey(),
        ]);
    }
}
