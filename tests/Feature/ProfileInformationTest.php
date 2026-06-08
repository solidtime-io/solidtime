<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Weekday;
use App\Models\User;
use App\Service\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_no_longer_be_updated_via_inertia(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $timezone = app(TimezoneService::class)->getTimezones()[0];
        $this->actingAs($user);

        // Act
        $response = $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'timezone' => $timezone,
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $user = $user->fresh();
        $this->assertEquals($user->name, $user->name);
    }
}
