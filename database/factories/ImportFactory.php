<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportSource;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'toggl_time_entries',
            'source' => ImportSource::File,
            'status' => ImportStatus::Pending,
            'timezone' => 'Europe/Vienna',
            'file_path' => null,
            'credentials' => null,
            'options' => null,
            'ignore_limits' => false,
            'report' => null,
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
            'status' => ImportStatus::Running,
            'started_at' => Carbon::now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Completed,
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'report' => [
                'clients' => ['created' => 1],
                'projects' => ['created' => 2],
                'tasks' => ['created' => 3],
                'time_entries' => ['created' => 4],
                'tags' => ['created' => 5],
                'users' => ['created' => 6],
            ],
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Failed,
            'started_at' => Carbon::now()->subMinute(),
            'finished_at' => Carbon::now(),
            'error_message' => 'Import failed',
        ]);
    }
}
