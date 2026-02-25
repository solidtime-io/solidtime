<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'role' => Role::Employee->value,
            'organization_id' => Organization::factory(),
            'accepted_at' => null,
        ];
    }

    public function role(Role $role): self
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role->value,
        ]);
    }

    public function accepted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_at' => $this->faker->dateTime(),
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }
}
