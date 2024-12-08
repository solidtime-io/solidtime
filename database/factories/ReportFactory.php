<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\Report;
use App\Service\Dto\ReportPropertiesDto;
use App\Service\ReportService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportDto = new ReportPropertiesDto;
        $reportDto->start = Carbon::createFromDate($this->faker->dateTimeBetween('-1 year', '-1 month'));
        $reportDto->end = Carbon::createFromDate($this->faker->dateTimeBetween('-1 month', 'now'));
        $reportDto->group = TimeEntryAggregationType::Project;
        $reportDto->subGroup = TimeEntryAggregationType::Task;
        $reportDto->historyGroup = TimeEntryAggregationTypeInterval::Day;
        $reportDto->weekStart = Weekday::from($this->faker->randomElement(Weekday::values()));
        $reportDto->timezone = $this->faker->timezone();

        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'is_public' => $this->faker->boolean(),
            'properties' => $reportDto,
            'organization_id' => Organization::factory(),
        ];
    }

    public function randomCreatedAt(): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function public(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
            'share_secret' => app(ReportService::class)->generateSecret(),
        ]);
    }

    public function private(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
            'share_secret' => null,
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->getKey(),
        ]);
    }
}
