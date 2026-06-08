<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Weekday;
use App\Mail\VerifyUpdatedEmailMail;
use App\Models\User;
use App\Service\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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

    public function test_pending_email_verification_redirects_with_danger_banner_when_email_already_in_use(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'taken@example.com',
            'is_placeholder' => false,
        ]);
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'taken@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'taken@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('bannerStyle', 'danger');
        $response->assertSessionHas('bannerText', 'The email address is already in use.');
        $user = $user->fresh();
        $this->assertEquals('current@example.com', $user->email);
        $this->assertEquals('taken@example.com', $user->pending_email);
    }

    public function test_stale_pending_email_verification_link_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'newer@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'older@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertForbidden();
        $user = $user->fresh();
        $this->assertEquals('current@example.com', $user->email);
        $this->assertEquals('newer@example.com', $user->pending_email);
    }
}
