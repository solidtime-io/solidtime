<?php

declare(strict_types=1);

namespace App\Console\Commands\Heartbeat;

use App\Jobs\HeartbeatQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HeartbeatSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heartbeat:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends the scheduler heartbeat and dispatches the queue heartbeat job to the configured monitoring URLs.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schedulerUrl = config('heartbeat.scheduler_url');
        $queueUrl = config('heartbeat.queue_url');

        if (! is_string($schedulerUrl) || $schedulerUrl === '') {
            $schedulerUrl = null;
        }
        if (! is_string($queueUrl) || $queueUrl === '') {
            $queueUrl = null;
        }

        if ($schedulerUrl === null && $queueUrl === null) {
            $this->info('No heartbeat URLs configured, nothing to do.');

            return self::SUCCESS;
        }

        if ($schedulerUrl !== null) {
            try {
                Http::timeout((int) config('heartbeat.timeout', 5))
                    ->get($schedulerUrl)
                    ->throw();
                $this->info('Scheduler heartbeat sent.');
            } catch (Throwable $exception) {
                Log::warning('Failed to send scheduler heartbeat', [
                    'exception' => $exception->getMessage(),
                ]);
                $this->warn('Failed to send scheduler heartbeat, check the logs for more information.');
            }
        }

        if ($queueUrl !== null) {
            HeartbeatQueueJob::dispatch(Carbon::now()->getTimestampMs());
            $this->info('Queue heartbeat job dispatched.');
        }

        return self::SUCCESS;
    }
}
