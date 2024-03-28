<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Service\ColorService;
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
            'color' => app(ColorService::class)->getRandomColor(),
            'billable_rate' => $this->faker->numberBetween(50, 1000) * 100,
            'is_public' => false,
            'client_id' => null,
            'organization_id' => Organization::factory(),
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

    public function isPublic(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'is_public' => true,
            ];
        });
    }

    public function isPrivate(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'is_public' => false,
            ];
        });
    }

    public function addMember(User $user, array $attributes = []): self
    {
        return $this->afterCreating(function (Project $project) use ($user, $attributes): void {
            ProjectMember::factory()
                ->forProject($project)
                ->forUser($user)
                ->create($attributes);
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
