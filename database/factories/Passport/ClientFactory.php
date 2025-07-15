<?php

declare(strict_types=1);

namespace Database\Factories\Passport;

use App\Models\Passport\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Passport\Database\Factories\ClientFactory as BaseClientFactory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends BaseClientFactory
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
            'owner_id' => null,
            'owner_type' => null,
            'name' => $this->faker->company(),
            'secret' => $this->faker->regexify('[A-Za-z]{40}'),
            'provider' => 'users',
            'redirect_uris' => [$this->faker->url()],
            'grant_types' => [],
            'revoked' => false,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function personalAccessClient(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'grant_types' => ['personal_access'],
            ];
        });
    }

    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user): array {
            return [
                'owner_id' => $user->getKey(),
                'owner_type' => (new User)->getMorphClass(),
            ];
        });
    }
}
