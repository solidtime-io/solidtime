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

    public function test_profile_information_can_be_updated(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'timezone' => app(TimezoneService::class)->getTimezones()[0],
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertValid(errorBag: 'updateProfileInformation');
        $user = $user->fresh();
        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals(app(TimezoneService::class)->getTimezones()[0], $user->timezone);
        $this->assertEquals(Weekday::Sunday, $user->week_start);
    }
}
