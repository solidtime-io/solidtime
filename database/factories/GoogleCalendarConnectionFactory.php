<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GoogleCalendarConnection>
 */
class GoogleCalendarConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'google_email' => $this->faker->safeEmail(),
            'refresh_token' => Str::random(64),
            'access_token' => null,
            'access_token_expires_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }
}
