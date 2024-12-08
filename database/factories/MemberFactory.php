<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billable_rate' => null,
            'role' => Role::Employee,
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(function (array $attributes) use ($role): array {
            return [
                'role' => $role->value,
            ];
        });
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->getKey(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
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

    public function attachToOrganization(Organization $organization, array $pivot = []): static
    {
        return $this->afterCreating(function (User $user) use ($organization, $pivot): void {
            $user->organizations()->attach($organization, $pivot);
        });
    }
}
