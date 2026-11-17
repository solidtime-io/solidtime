<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\HeartbeatQueueJob;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(HeartbeatQueueJob::class)]
class HeartbeatQueueJobTest extends TestCase
{
    public function test_replaces_latency_placeholder_with_time_between_dispatch_and_execution(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::createFromTimestampMs(1_700_000_001_500));
        config([
            'heartbeat.queue_url' => 'https://monitor.example.com/queue?status=up&ping={latency_ms}',
        ]);
        Http::fake();
        $job = new HeartbeatQueueJob(1_700_000_000_250);

        // Act
        $job->handle();

        // Assert
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://monitor.example.com/queue?status=up&ping=1250'
            && $request->method() === 'GET');
    }

    public function test_calls_url_unchanged_if_it_has_no_placeholder(): void
    {
        // Arrange
        config([
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
        ]);
        Http::fake();
        $job = new HeartbeatQueueJob(Carbon::now()->getTimestampMs());

        // Act
        $job->handle();

        // Assert
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://monitor.example.com/queue');
    }

    public function test_latency_is_never_negative(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::createFromTimestampMs(1_700_000_000_000));
        config([
            'heartbeat.queue_url' => 'https://monitor.example.com/queue?ping={latency_ms}',
        ]);
        Http::fake();
        $job = new HeartbeatQueueJob(1_700_000_005_000);

        // Act
        $job->handle();

        // Assert
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://monitor.example.com/queue?ping=0');
    }

    public function test_does_nothing_if_queue_url_is_not_configured(): void
    {
        // Arrange
        config([
            'heartbeat.queue_url' => null,
        ]);
        Http::fake();
        $job = new HeartbeatQueueJob(Carbon::now()->getTimestampMs());

        // Act
        $job->handle();

        // Assert
        Http::assertNothingSent();
    }

    public function test_does_not_throw_if_request_fails(): void
    {
        // Arrange
        config([
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
        ]);
        Http::fake([
            'https://monitor.example.com/queue' => function (): void {
                throw new ConnectionException('Connection timed out');
            },
        ]);
        $job = new HeartbeatQueueJob(Carbon::now()->getTimestampMs());

        // Act
        $job->handle();

        // Assert
        Http::assertSentCount(0);
    }

    public function test_does_not_throw_if_response_has_error_status(): void
    {
        // Arrange
        config([
            'heartbeat.queue_url' => 'https://monitor.example.com/queue',
        ]);
        Http::fake([
            'https://monitor.example.com/queue' => Http::response(null, 500),
        ]);
        $job = new HeartbeatQueueJob(Carbon::now()->getTimestampMs());

        // Act
        $job->handle();

        // Assert
        Http::assertSentCount(1);
    }
}
