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

    public function test_show_profile_information_succeeds(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertSuccessful();
    }

    public function test_profile_information_can_be_updated(): void
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
        $response->assertValid(errorBag: 'updateProfileInformation');
        $user = $user->fresh();
        $this->assertEquals('Test Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals($timezone, $user->timezone);
        $this->assertEquals(Weekday::Sunday, $user->week_start);
    }

    public function test_email_update_keeps_current_email_verified_until_new_email_is_verified(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'email_verified_at' => now(),
        ]);
        $timezone = app(TimezoneService::class)->getTimezones()[0];
        $this->actingAs($user);

        // Act
        $response = $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'New.Email@Example.com',
            'timezone' => $timezone,
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertValid(errorBag: 'updateProfileInformation');
        $user = $user->fresh();
        $this->assertEquals('current@example.com', $user->email);
        $this->assertEquals('new.email@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Mail::assertSent(VerifyUpdatedEmailMail::class, function (VerifyUpdatedEmailMail $mail): bool {
            return $mail->hasTo('new.email@example.com') && $mail->email === 'new.email@example.com';
        });
    }

    public function test_pending_email_can_be_verified(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new.email@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'new.email@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertRedirect(route('dashboard', [
            'bannerStyle' => 'success',
            'bannerText' => 'Your email address has been updated successfully.',
        ]));
        $user = $user->fresh();
        $this->assertEquals('new.email@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_profile_update_does_not_clear_pending_email_when_email_is_unchanged(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new.email@example.com',
        ]);
        $timezone = app(TimezoneService::class)->getTimezones()[0];
        $this->actingAs($user);

        // Act
        $response = $this->put('/user/profile-information', [
            'name' => 'Updated Name',
            'email' => 'current@example.com',
            'timezone' => $timezone,
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertValid(errorBag: 'updateProfileInformation');
        $user = $user->fresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('current@example.com', $user->email);
        $this->assertEquals('new.email@example.com', $user->pending_email);
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
