<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
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
            'is_billable' => false,
            'billable_rate' => null,
            'is_public' => false,
            'archived_at' => null,
            'client_id' => null,
            'organization_id' => Organization::factory(),
            'estimated_time' => null,
        ];
    }

    public function withEstimatedTime(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'estimated_time' => $this->faker->randomNumber(3),
            ];
        });
    }

    public function billable(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'is_billable' => true,
                'billable_rate' => $this->faker->numberBetween(50, 1000) * 100,
            ];
        });
    }

    public function archived(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'archived_at' => $this->faker->dateTime(),
            ];
        });
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

    public function addMember(Member $member, array $attributes = []): self
    {
        return $this->afterCreating(function (Project $project) use ($member, $attributes): void {
            ProjectMember::factory()
                ->forProject($project)
                ->forMember($member)
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
