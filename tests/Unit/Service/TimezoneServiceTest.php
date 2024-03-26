<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\User;
use App\Service\TimezoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use TiMacDonald\Log\LogEntry;

class TimezoneServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_timezones_returns_all_available_timezones(): void
    {
        // Arrange
        $service = app(TimezoneService::class);

        // Act
        $result = $service->getTimezones();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(419, $result);
        $this->assertContains('Europe/Vienna', $result);
        $this->assertContains('Europe/Berlin', $result);
        $this->assertContains('Europe/London', $result);
    }

    public function test_get_timezone_from_user_returns_timezone_of_user_as_carbon_timezone(): void
    {
        // Arrange
        $user = User::factory()->create([
            'timezone' => 'Europe/Berlin',
        ]);

        /** @var TimezoneService $service */
        $service = app(TimezoneService::class);

        // Act
        $result = $service->getTimezoneFromUser($user);

        // Assert
        $this->assertEquals('Europe/Berlin', $result->getName());
    }

    public function test_get_timezone_from_user_falls_back_to_utc_and_logs_this_failure_if_timezone_in_db_is_corrupt(): void
    {
        // Arrange
        $corruptTimezone = 'Invalid/Timezone';
        $user = User::factory()->create([
            'timezone' => $corruptTimezone,
        ]);

        /** @var TimezoneService $service */
        $service = app(TimezoneService::class);

        // Act
        $result = $service->getTimezoneFromUser($user);

        // Assert
        $this->assertEquals('UTC', $result->getName());
        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error'
            && $log->message === 'User has a invalid timezone'
        );
    }
}
