<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\UserGoogleCalendarController;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserGoogleCalendarController::class)]
class GoogleCalendarEndpointTest extends ApiEndpointTestAbstract
{
    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
    }

    public function test_show_returns_not_connected_if_user_has_no_connection(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.show'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'available' => true,
                'connected' => false,
                'google_email' => null,
            ],
        ]);
    }

    public function test_show_returns_connection_details_if_user_has_a_connection(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create([
            'google_email' => 'calendar@example.com',
        ]);
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.show'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'available' => true,
                'connected' => true,
                'google_email' => 'calendar@example.com',
            ],
        ]);
    }

    public function test_events_fails_if_user_has_no_connection(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events', [
            'start' => '2026-07-13T00:00:00Z',
            'end' => '2026-07-20T00:00:00Z',
        ]));

        // Assert
        $this->assertResponseCode($response, 400);
        $response->assertJsonPath('key', 'google_calendar_not_connected');
    }

    public function test_events_validates_date_parameters(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events'));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_events_returns_events_from_google_calendar(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create([
            'access_token' => 'cached-access-token',
            'access_token_expires_at' => Carbon::now()->addMinutes(30),
        ]);
        Passport::actingAs($user);
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [
                    [
                        'id' => 'event-1',
                        'status' => 'confirmed',
                        'summary' => 'Weekly team meeting',
                        'htmlLink' => 'https://www.google.com/calendar/event?eid=1',
                        'start' => ['dateTime' => '2026-07-14T10:00:00+02:00'],
                        'end' => ['dateTime' => '2026-07-14T10:30:00+02:00'],
                    ],
                    [
                        'id' => 'event-2',
                        'status' => 'cancelled',
                        'summary' => 'Cancelled meeting',
                        'start' => ['dateTime' => '2026-07-14T11:00:00+02:00'],
                        'end' => ['dateTime' => '2026-07-14T11:30:00+02:00'],
                    ],
                    [
                        'id' => 'event-3',
                        'status' => 'confirmed',
                        'summary' => 'Declined meeting',
                        'start' => ['dateTime' => '2026-07-14T12:00:00+02:00'],
                        'end' => ['dateTime' => '2026-07-14T12:30:00+02:00'],
                        'attendees' => [
                            ['self' => true, 'responseStatus' => 'declined'],
                        ],
                    ],
                    [
                        'id' => 'event-4',
                        'status' => 'confirmed',
                        'summary' => 'All-day event',
                        'start' => ['date' => '2026-07-15'],
                        'end' => ['date' => '2026-07-16'],
                    ],
                ],
            ], 200),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events', [
            'start' => '2026-07-13T00:00:00Z',
            'end' => '2026-07-20T00:00:00Z',
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(2, 'data');
        $response->assertJson([
            'data' => [
                [
                    'id' => 'event-1',
                    'summary' => 'Weekly team meeting',
                    'start' => '2026-07-14T10:00:00+02:00',
                    'end' => '2026-07-14T10:30:00+02:00',
                    'all_day' => false,
                    'html_link' => 'https://www.google.com/calendar/event?eid=1',
                ],
                [
                    'id' => 'event-4',
                    'summary' => 'All-day event',
                    'all_day' => true,
                ],
            ],
        ]);
        Http::assertSentCount(1);
    }

    public function test_events_refreshes_the_access_token_if_it_is_expired(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->forUser($user)->create([
            'access_token' => 'expired-access-token',
            'access_token_expires_at' => Carbon::now()->subMinutes(5),
        ]);
        Passport::actingAs($user);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 3599,
            ], 200),
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events', [
            'start' => '2026-07-13T00:00:00Z',
            'end' => '2026-07-20T00:00:00Z',
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
        $connection->refresh();
        $this->assertSame('new-access-token', $connection->access_token);
        Http::assertSentCount(2);
    }

    public function test_events_fails_with_broken_connection_if_token_refresh_fails(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create();
        Passport::actingAs($user);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events', [
            'start' => '2026-07-13T00:00:00Z',
            'end' => '2026-07-20T00:00:00Z',
        ]));

        // Assert
        $this->assertResponseCode($response, 400);
        $response->assertJsonPath('key', 'google_calendar_connection_broken');
    }

    public function test_events_fails_if_time_range_is_too_long(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create();
        Passport::actingAs($user);

        // Act
        $response = $this->getJson(route('api.v1.users.google-calendar.events', [
            'start' => '2026-01-01T00:00:00Z',
            'end' => '2026-07-20T00:00:00Z',
        ]));

        // Assert
        $response->assertStatus(422);
    }
}
