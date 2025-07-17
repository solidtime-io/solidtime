<?php

declare(strict_types=1);

namespace Database\Factories\Passport;

use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Token>
 */
class TokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'user_id' => null,
            'client_id' => $this->faker->uuid,
            'name' => null,
            'scopes' => [],
            'revoked' => false,
            'created_at' => $this->faker->dateTime,
            'updated_at' => $this->faker->dateTime,
            'expires_at' => $this->faker->dateTime,
            'reminder_sent_at' => null,
            'expired_info_sent_at' => null,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }

    public function forClient(Client $client): self
    {
        return $this->state(function (array $attributes) use ($client): array {
            return [
                'client_id' => $client->getKey(),
            ];
        });
    }
}
