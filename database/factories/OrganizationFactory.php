<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Models\Organization;
use App\Models\User;
use App\Service\CurrencyService;
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
            'currency' => app(CurrencyService::class)->getRandomCurrencyCode(),
            'billable_rate' => null,
            'user_id' => User::factory(),
            'personal_team' => true,
            'employees_can_see_billable_rates' => false,
            'number_format' => $this->faker->randomElement(NumberFormat::values()),
            'currency_format' => $this->faker->randomElement(CurrencyFormat::values()),
            'date_format' => $this->faker->randomElement(DateFormat::values()),
            'interval_format' => $this->faker->randomElement(IntervalFormat::values()),
            'time_format' => $this->faker->randomElement(TimeFormat::values()),
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

    public function withFakeId(): self
    {
        return $this->state(fn (array $attributes) => [
            'id' => $this->faker->uuid(),
        ]);
    }
}
