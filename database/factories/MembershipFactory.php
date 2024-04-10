<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => Role::Employee,
            'organization_id' => OrganizationFactory::class,
            'user_id' => UserFactory::class,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(function (array $attributes) use ($organization): array {
            return [
                'organization_id' => $organization->getKey(),
            ];
        });
    }

    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
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

    public function attachToOrganization(Organization $organization, array $pivot = []): static
    {
        return $this->afterCreating(function (User $user) use ($organization, $pivot) {
            $user->organizations()->attach($organization, $pivot);
        });
    }
}
