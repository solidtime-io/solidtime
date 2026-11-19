<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Heartbeat;

use App\Console\Commands\Heartbeat\HeartbeatSendCommand;
use App\Jobs\HeartbeatQueueJob;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use TiMacDonald\Log\LogEntry;

#[CoversClass(HeartbeatSendCommand::class)]
class HeartbeatSendCommandTest extends TestCase
{
    public function test_sends_scheduler_heartbeat_and_dispatches_queue_job_if_both_urls_are_configured(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::createFromTimestampMs(1_700_000_000_123));
        config([
            'heartbeat.scheduler_url' => 'https://monitor.example.com/scheduler',
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
            'queue.default' => 'database',
        ]);
        Http::fake([
            'https://monitor.example.com/scheduler' => Http::response('OK', 200),
        ]);
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://monitor.example.com/scheduler'
            && $request->method() === 'GET');
        Queue::assertPushed(HeartbeatQueueJob::class, fn (HeartbeatQueueJob $job): bool => $job->dispatchedAtMs === 1_700_000_000_123);
    }

    public function test_only_sends_scheduler_heartbeat_if_queue_url_is_not_configured(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => 'https://monitor.example.com/scheduler',
            'heartbeat.queue_url' => null,
        ]);
        Http::fake([
            'https://monitor.example.com/scheduler' => Http::response('OK', 200),
        ]);
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Http::assertSentCount(1);
        Queue::assertNotPushed(HeartbeatQueueJob::class);
    }

    public function test_only_dispatches_queue_job_if_scheduler_url_is_not_configured(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => null,
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
            'queue.default' => 'database',
        ]);
        Http::fake();
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Http::assertNothingSent();
        Queue::assertPushed(HeartbeatQueueJob::class);
    }

    public function test_does_nothing_if_no_url_is_configured(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => null,
            'heartbeat.queue_url' => '',
        ]);
        Http::fake();
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_succeeds_and_still_dispatches_queue_job_if_scheduler_heartbeat_returns_error_status(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => 'https://monitor.example.com/scheduler',
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
            'queue.default' => 'database',
        ]);
        Http::fake([
            'https://monitor.example.com/scheduler' => Http::response(null, 500),
        ]);
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Queue::assertPushed(HeartbeatQueueJob::class);
    }

    public function test_succeeds_if_scheduler_heartbeat_times_out(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => 'https://monitor.example.com/scheduler',
            'heartbeat.queue_url' => null,
        ]);
        Http::fake([
            'https://monitor.example.com/scheduler' => function (): void {
                throw new ConnectionException('Connection timed out');
            },
        ]);
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function test_skips_queue_heartbeat_if_queue_connection_uses_sync_driver(): void
    {
        // Arrange
        config([
            'heartbeat.scheduler_url' => 'https://monitor.example.com/scheduler',
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
            'queue.default' => 'sync',
        ]);
        Http::fake([
            'https://monitor.example.com/scheduler' => Http::response('OK', 200),
        ]);
        Queue::fake();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('heartbeat:send');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://monitor.example.com/scheduler');
        Queue::assertNotPushed(HeartbeatQueueJob::class);
        Log::assertLoggedTimes(fn (LogEntry $log): bool => $log->level === 'warning'
            && $log->message === 'Skipped queue heartbeat, because the queue connection uses the sync driver'
            && $log->context === ['queue_connection' => 'sync'],
            1
        );
    }
}
