<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\User;
use App\Service\TimezoneService;
use Carbon\CarbonTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use TiMacDonald\Log\LogEntry;

#[CoversClass(TimezoneService::class)]
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
        $this->assertTrue(in_array(count($result), [418, 419], true));
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

    public function test_get_local_date_time_sql_expression_binds_named_timezones(): void
    {
        // Arrange
        $timezoneService = app(TimezoneService::class);

        // Act
        $expression = $timezoneService->getLocalDateTimeSqlExpression('start', new CarbonTimeZone('Europe/Vienna'));

        // Assert
        $this->assertSame([
            'sql' => '("start" AT TIME ZONE \'UTC\' AT TIME ZONE CAST(? AS text))',
            'bindings' => ['Europe/Vienna'],
        ], $expression);
    }

    public function test_get_local_date_time_sql_expression_binds_the_shift_for_offset_timezones(): void
    {
        // Arrange
        $timezoneService = app(TimezoneService::class);

        // Act
        $positive = $timezoneService->getLocalDateTimeSqlExpression('start', new CarbonTimeZone('+02:00'));
        $negative = $timezoneService->getLocalDateTimeSqlExpression('start', new CarbonTimeZone('-05:30'));

        // Assert
        $this->assertSame('("start" + make_interval(secs => CAST(? AS integer)))', $positive['sql']);
        $this->assertSame([7200], $positive['bindings']);
        $this->assertSame([-19800], $negative['bindings']);
    }

    public function test_get_local_date_time_sql_expression_rejects_invalid_column_names(): void
    {
        // Arrange
        $timezoneService = app(TimezoneService::class);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $timezoneService->getLocalDateTimeSqlExpression('start; drop table users', new CarbonTimeZone('UTC'));
    }
}
