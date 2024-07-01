<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
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

    public function forCurrentOrganization(Organization $organization): static
    {
        return $this->state(function (array $attributes) use ($organization): array {
            return [
                'current_team_id' => $organization->getKey(),
            ];
        });
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

    public function attachToOrganization(Organization $organization, array $pivot = []): static
    {
        return $this->afterCreating(function (User $user) use ($organization, $pivot) {
            $user->organizations()->attach($organization, $pivot);
        });
    }

    public function withProfilePicture(): static
    {
        $profilePhoto = $this->faker->image(null, 500, 500);
        /** @see \Illuminate\Http\FileHelpers::hashName */
        $path = 'profile-photos/'.Str::random(40).'.png';
        Storage::disk(config('jetstream.profile_photo_disk', 'public'))->put($path, $profilePhoto);

        return $this->state(function (array $attributes) use ($path): array {
            return [
                'profile_photo_path' => $path,
            ];
        });
    }

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalOrganization(?callable $callback = null): static
    {
        return $this->afterCreating(function (User $user) use ($callback): void {
            $organization = Organization::factory()
                ->state(fn (array $attributes) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                    'personal_team' => true,
                ])
                ->when(is_callable($callback), $callback)
                ->create();

            $organization->owner()->associate($user);
            $organization->users()->attach($user, ['role' => Role::Owner->value]);
            $user->currentTeam()->associate($organization);
            $user->save();
        });
    }
}
