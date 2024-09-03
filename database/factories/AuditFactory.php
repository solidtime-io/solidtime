<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        return [
            $morphPrefix.'_id' => function () {
                return User::factory()->create()->id;
            },
            $morphPrefix.'_type' => function () {
                return (new User())->getMorphClass();
            },
            'event' => 'updated',
            'auditable_id' => function () {
                return User::factory()->create()->getKey();
            },
            'auditable_type' => function () {
                return (new User())->getMorphClass();
            },
            'old_values' => [],
            'new_values' => [],
            'url' => $this->faker->url,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'tags' => implode(',', $this->faker->words(4)),
        ];
    }

    public function auditUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

            return [
                $morphPrefix.'_id' => $user->getKey(),
                $morphPrefix.'_type' => $user->getMorphClass(),
            ];
        });
    }

    public function auditFor(Model $model): self
    {
        return $this->state(function (array $attributes) use ($model) {
            return [
                'auditable_id' => $model->getKey(),
                'auditable_type' => $model->getMorphClass(),
            ];
        });
    }
}
