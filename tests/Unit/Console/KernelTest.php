<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Kernel::class)]
class KernelTest extends TestCase
{
    public function test_self_host_commands_schedule_time_is_consistent_with_app_key(): void
    {
        // Arrange
        config([
            'app.key' => 'base64:cOXN4GLMXYjcdG0fKosnFogofXw1pNoXkLAViRH+a5Y=',
        ]);

        // Act
        $schedule1 = app()->make(Kernel::class)->resolveConsoleSchedule();
        $firstRunEvents = collect($schedule1->events())->filter(fn ($event) => str_contains($event->command, 'self-host:check-for-update') ||
            str_contains($event->command, 'self-host:telemetry')
        );

        $schedule2 = app()->make(Kernel::class)->resolveConsoleSchedule();
        $secondRunEvents = collect($schedule2->events())->filter(fn ($event) => str_contains($event->command, 'self-host:check-for-update') ||
            str_contains($event->command, 'self-host:telemetry')
        );
        config([
            'app.key' => 'base64:eP58hkQ8l3guqf8wvWJR7pB0weVQtnpjMdYpaVwX4Jw=',
        ]);
        $schedule3 = app()->make(Kernel::class)->resolveConsoleSchedule();
        $thirdRunEvents = collect($schedule3->events())->filter(fn ($event) => str_contains($event->command, 'self-host:check-for-update') ||
            str_contains($event->command, 'self-host:telemetry')
        );

        // Assert
        $this->assertCount(2, $firstRunEvents);
        $this->assertCount(2, $secondRunEvents);
        $this->assertCount(2, $thirdRunEvents);

        foreach ($firstRunEvents as $index => $event) {
            $this->assertSame('52 9,21 * * *', $firstRunEvents[$index]->expression);
            $this->assertSame('52 9,21 * * *', $secondRunEvents[$index]->expression);
            $this->assertSame('48 13,1 * * *', $thirdRunEvents[$index]->expression);
        }
    }

    public function test_self_hosting_telemetry_can_be_activated(): void
    {
        // Arrange
        config([
            'scheduling.tasks.self_hosting_telemetry' => true,
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'self-host:telemetry')
        );

        // Assert
        $this->assertCount(1, $events);
    }

    public function test_self_hosting_telemetry_can_be_deactivated(): void
    {
        // Arrange
        config([
            'scheduling.tasks.self_hosting_telemetry' => false,
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'self-host:telemetry')
        );

        // Assert
        $this->assertCount(0, $events);
    }

    public function test_self_hosting_check_for_update_can_be_activated(): void
    {
        // Arrange
        config([
            'scheduling.tasks.self_hosting_check_for_update' => true,
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'self-host:check-for-update')
        );

        // Assert
        $this->assertCount(1, $events);
    }

    public function test_self_hosting_check_for_update_can_be_deactivated(): void
    {
        // Arrange
        config([
            'scheduling.tasks.self_hosting_check_for_update' => false,
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'self-host:check-for-update')
        );

        // Assert
        $this->assertCount(0, $events);
    }

    public function test_heartbeat_runs_every_minute_if_a_heartbeat_url_is_configured(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => null,
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'heartbeat:send'))->values();

        // Assert
        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events[0]->expression);
        $this->assertTrue($events[0]->onOneServer);
        $this->assertTrue($events[0]->filtersPass(app()));
    }

    public function test_heartbeat_does_not_run_if_no_heartbeat_url_is_configured(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => null,
            'heartbeat.queue_url' => null,
        ]);

        // Act
        $schedule = app()->make(Kernel::class)->resolveConsoleSchedule();
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'heartbeat:send'))->values();

        // Assert
        $this->assertCount(1, $events);
        $this->assertFalse($events[0]->filtersPass(app()));
    }
}
