<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Models\Export;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ExportType::Organization,
            'status' => ExportStatus::Pending,
            'file_path' => null,
            'error_message' => null,
            'error_details' => null,
            'started_at' => null,
            'finished_at' => null,
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function forUser(?User $user): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user?->getKey(),
        ]);
    }

    public function running(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExportStatus::Running,
            'started_at' => Carbon::now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExportStatus::Completed,
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'file_path' => 'exports/'.fake()->uuid().'/'.fake()->uuid().'.zip',
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExportStatus::Failed,
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'error_message' => 'Export failed',
        ]);
    }
}
