<?php

declare(strict_types=1);

namespace Database\Factories\Passport;

use App\Models\Passport\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'name' => $this->faker->company(),
            'secret' => $this->faker->regexify('[A-Za-z]{40}'),
            'provider' => 'users',
            'redirect' => $this->faker->url(),
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function personalAccessClient(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'personal_access_client' => true,
            ];
        });
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }
}
