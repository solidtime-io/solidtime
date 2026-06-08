<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\Weekday;
use App\Events\NewsletterRegistered;
use App\Models\Member;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Service\IpLookup\IpLookupResponseDto;
use App\Service\IpLookup\IpLookupServiceContract;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Features;
use Tests\TestCaseWithDatabase;
use TiMacDonald\Log\LogEntry;

class RegistrationTest extends TestCaseWithDatabase
{
    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Arrange
        Event::fake([
            NewsletterRegistered::class,
        ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        // Assert
        $response->assertValid();
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Test User', $user->name);
        $this->assertSame('UTC', $user->timezone);
        $organization = $user->organizations()->firstOrFail();
        $this->assertSame(true, $organization->personal_team);
        $member = Member::query()->whereBelongsTo($user, 'user')->whereBelongsTo($organization, 'organization')->firstOrFail();
        $this->assertSame(Role::Owner->value, $member->role);
        Event::assertNotDispatched(NewsletterRegistered::class);
        $this->assertSame($organization->getKey(), $user->current_team_id);
    }

    public function test_user_registration_fails_if_registration_is_deactivated(): void
    {
        // Arrange
        Event::fake([
            NewsletterRegistered::class,
        ]);
        Config::set('app.enable_registration', false);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        // Assert
        $response->assertInvalid([
            'email' => 'Registration is disabled.',
        ]);
        $this->assertFalse(User::query()->where('email', 'test@example.com')->exists());
        Event::assertNotDispatched(NewsletterRegistered::class);
    }

    public function test_new_user_can_not_register_with_likely_invalid_domain(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'peter.test@gmail',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        // Assert
        $response->assertInvalid(['email']);
    }

    public function test_new_user_can_register_with_uppercase_email(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'PETER.test@gmail.com ',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        // Assert
        $response->assertValid(['email']);
    }

    public function test_new_users_can_consent_to_newsletter_during_registration(): void
    {
        // Arrange
        Event::fake([
            NewsletterRegistered::class,
        ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'newsletter_consent' => true,
        ]);

        // Assert
        $response->assertValid();
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Test User', $user->name);
        $this->assertSame('UTC', $user->timezone);
        Event::assertDispatched(NewsletterRegistered::class);
    }

    public function test_new_users_can_register_and_frontend_can_send_timezone_for_user(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => 'Europe/Berlin',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Europe/Berlin', $user->timezone);
    }

    public function test_new_users_can_register_and_uses_ip_lookup_service_to_get_information_about_currency_and_start_of_week(): void
    {
        // Arrange
        $this->mock(IpLookupServiceContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')->andReturn(new IpLookupResponseDto(
                'America/New_York',
                Weekday::Sunday,
                'USD',
            ));
        });

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => 'Europe/Berlin',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        /** @var User $user */
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Europe/Berlin', $user->timezone);
        $this->assertSame(Weekday::Sunday, $user->week_start);
        $this->assertSame('USD', $user->organizations->first()->currency);
    }

    public function test_new_users_can_register_and_uses_ip_lookup_service_to_get_information_about_timezone_if_client_did_not_send_one(): void
    {
        // Arrange
        $this->mock(IpLookupServiceContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')->andReturn(new IpLookupResponseDto(
                'America/New_York',
                Weekday::Sunday,
                'USD',
            ));
        });

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => null,
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        /** @var User $user */
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertSame(Weekday::Sunday, $user->week_start);
        $this->assertSame('USD', $user->organizations->first()->currency);
    }

    public function test_new_users_can_register_and_uses_ip_lookup_service_to_get_information_about_timezone_if_client_sends_invalid_one(): void
    {
        // Arrange
        $this->mock(IpLookupServiceContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')->andReturn(new IpLookupResponseDto(
                'America/New_York',
                Weekday::Sunday,
                'USD',
            ));
        });

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => 'Unknown timezone',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        /** @var User $user */
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertSame(Weekday::Sunday, $user->week_start);
        $this->assertSame('USD', $user->organizations->first()->currency);
    }

    public function test_new_users_can_register_and_legacy_timezone_from_client_is_mapped_to_new_timezone(): void
    {
        // Arrange
        $this->mock(IpLookupServiceContract::class, function ($mock): void {
            $mock->shouldReceive('lookup')->andReturn(new IpLookupResponseDto(
                'America/New_York',
                Weekday::Sunday,
                'USD',
            ));
        });

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => 'Asia/Calcutta',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        /** @var User $user */
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Asia/Kolkata', $user->timezone);
        $this->assertSame(Weekday::Sunday, $user->week_start);
        $this->assertSame('USD', $user->organizations->first()->currency);
    }

    public function test_new_users_can_register_and_ignores_invalid_timezones_from_frontend(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'timezone' => 'Unknown timezone',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('UTC', $user->timezone);
    }

    public function test_new_users_can_not_register_if_user_with_email_already_exists(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $this->assertFalse($this->isAuthenticated(), 'The user is authenticated');
        $response->assertInvalid(['email']);
    }

    public function test_new_users_can_register_if_placeholder_user_with_email_already_exists(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_placeholder' => true,
        ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_does_not_create_private_organization_if_invite_was_accepted_for_the_email_with_the_registration_email(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $organizationInvitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->role(Role::Employee)
            ->accepted()
            ->create([
                'email' => 'test@example.com',
            ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $newUser = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertDatabaseMissing(OrganizationInvitation::class, [
            'email' => 'test@example.com',
        ]);
        $organizations = $newUser->organizations;
        $this->assertCount(1, $organizations);
        $this->assertSame($user->organization->id, $organizations->first()->id);
    }

    public function test_registration_logs_and_skips_accepted_invitation_with_invalid_role(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $organizationInvitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->accepted()
            ->create([
                'email' => 'test@example.com',
                'role' => 'invalid-role',
            ]);

        // Act
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error'
            && $log->message === 'Invalid role in invitation'
            && $log->context === [
                'invitation' => $organizationInvitation->getKey(),
                'role' => 'invalid-role',
            ]);
        $newUser = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertDatabaseHas(OrganizationInvitation::class, [
            'id' => $organizationInvitation->getKey(),
            'email' => 'test@example.com',
            'role' => 'invalid-role',
        ]);
        $this->assertDatabaseMissing(Member::class, [
            'organization_id' => $user->organization->getKey(),
            'user_id' => $newUser->getKey(),
        ]);
        $organizations = $newUser->organizations;
        $this->assertCount(1, $organizations);
        $this->assertNotSame($user->organization->id, $organizations->first()->id);
    }
}
