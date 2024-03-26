<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
            'is_placeholder' => false,
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Monday,
        ];
    }

    public function randomTimeZone(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'timezone' => $this->faker->timezone(),
            ];
        });
    }

    public function placeholder(bool $placeholder = true): static
    {
        return $this->state(function (array $attributes) use ($placeholder): array {
            return [
                'is_placeholder' => $placeholder,
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

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalOrganization(?callable $callback = null): static
    {
        return $this->has(
            Organization::factory()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                    'personal_team' => true,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        );
    }
}
