<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\UserProfileController;
use App\Models\Passport\Client;
use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserProfileController::class)]
class UserProfileEndpointTest extends EndpointTestAbstract
{
    public function test_showing_profile_succeeds_and_exposes_profile_settings_data(): void
    {
        // Arrange
        config(['session.driver' => 'array']);
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('confirmsTwoFactorAuthentication', true)
            ->where('sessions', [])
        );
    }

    public function test_showing_profile_exposes_database_sessions_for_current_user(): void
    {
        // Arrange
        config(['session.driver' => 'database']);
        $this->travelTo(Carbon::parse('2024-01-02 12:00:00', 'UTC'));
        $user = User::factory()->withPersonalOrganization()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        DB::table('sessions')->insert([
            [
                'id' => 'older-session',
                'user_id' => $user->getKey(),
                'ip_address' => '192.0.2.10',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'payload' => '',
                'last_activity' => now()->subMinutes(5)->timestamp,
            ],
            [
                'id' => 'newer-session',
                'user_id' => $user->getKey(),
                'ip_address' => '192.0.2.20',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'payload' => '',
                'last_activity' => now()->subMinute()->timestamp,
            ],
            [
                'id' => 'other-user-session',
                'user_id' => $otherUser->getKey(),
                'ip_address' => '192.0.2.30',
                'user_agent' => '',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
        ]);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->has('sessions', 2)
            ->where('sessions.0.agent.is_desktop', true)
            ->where('sessions.0.agent.platform', 'Linux')
            ->where('sessions.0.agent.browser', 'Chrome')
            ->where('sessions.0.ip_address', '192.0.2.20')
            ->where('sessions.0.is_current_device', false)
            ->where('sessions.0.last_active', '1 minute ago')
            ->where('sessions.1.agent.is_desktop', true)
            ->where('sessions.1.agent.platform', 'OS X')
            ->where('sessions.1.agent.browser', 'Chrome')
            ->where('sessions.1.ip_address', '192.0.2.10')
            ->where('sessions.1.is_current_device', false)
            ->where('sessions.1.last_active', '5 minutes ago')
        );
    }

    public function test_showing_profile_marks_two_factor_authentication_as_empty_when_disabled(): void
    {
        // Arrange
        config(['session.driver' => 'array']);
        $user = User::factory()->withPersonalOrganization()->create([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertOk();
        $response->assertSessionHas('two_factor_empty_at');
    }

    public function test_showing_profile_disables_unconfirmed_two_factor_authentication_after_confirmation_was_abandoned(): void
    {
        // Arrange
        config(['session.driver' => 'array']);
        $user = User::factory()->withPersonalOrganization()->create([
            'two_factor_secret' => 'secret',
            'two_factor_recovery_codes' => '[]',
            'two_factor_confirmed_at' => null,
        ]);
        $this->actingAs($user);
        $this->withSession(['two_factor_confirming_at' => time() - 1]);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertOk();
        $response->assertSessionHas('two_factor_empty_at');
        $response->assertSessionMissing('two_factor_confirming_at');
        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_showing_profile_exposes_active_app_sessions_of_current_user(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-02 12:00:00', 'UTC'));
        $user = User::factory()->withPersonalOrganization()->create();
        $otherUser = User::factory()->create();
        $desktopClient = Client::factory()->desktopClient()->create();
        $personalAccessClient = Client::factory()->personalAccessClient()->create();
        $activeToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'created_at' => now()->subHours(2),
            'expires_at' => now()->addDay(),
        ]);
        $expiredTokenWithValidRefreshToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'created_at' => now()->subDays(3),
            'expires_at' => now()->subDay(),
        ]);
        RefreshToken::query()->forceCreate([
            'id' => 'valid-refresh-token',
            'access_token_id' => $expiredTokenWithValidRefreshToken->id,
            'revoked' => false,
            'expires_at' => now()->addMonth(),
        ]);
        Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'revoked' => true,
            'expires_at' => now()->addDay(),
        ]);
        Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'expires_at' => now()->subDay(),
        ]);
        Token::factory()->forUser($user)->forClient($personalAccessClient)->create([
            'expires_at' => now()->addDay(),
        ]);
        Token::factory()->forUser($otherUser)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get('/user/profile');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->has('appSessions', 2)
            ->where('appSessions.0.id', $activeToken->id)
            ->where('appSessions.0.name', 'Desktop')
            ->where('appSessions.0.last_active', '2 hours ago')
            ->where('appSessions.1.id', $expiredTokenWithValidRefreshToken->id)
            ->where('appSessions.1.last_active', '3 days ago')
        );
    }
}
