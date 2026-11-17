<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeartbeatQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * A heartbeat is not retried, since a retried heartbeat would be stale.
     */
    public int $tries = 1;

    /**
     * @param  int  $dispatchedAtMs  Unix timestamp in milliseconds of when the job was dispatched
     */
    public function __construct(
        public readonly int $dispatchedAtMs
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $queueUrl = config('heartbeat.queue_url');
        if (! is_string($queueUrl) || $queueUrl === '') {
            return;
        }

        $latencyMs = max(0, Carbon::now()->getTimestampMs() - $this->dispatchedAtMs);
        $url = str_replace('{latency_ms}', (string) $latencyMs, $queueUrl);

        try {
            Http::timeout((int) config('heartbeat.timeout', 5))
                ->get($url)
                ->throw();
        } catch (Throwable $exception) {
            Log::warning('Failed to send queue heartbeat', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
