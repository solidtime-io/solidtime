<?php

declare(strict_types=1);

namespace App\Console\Commands\SelfHost;

use App\Service\ApiService;
use Illuminate\Console\Command;

class SelfHostTelemetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'self-host:telemetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiService = app(ApiService::class);

        $success = $apiService->telemetry();

        if (! $success) {
            $this->error('Failed to send telemetry data, check the logs for more information.');

            return self::FAILURE;

        }

        return self::SUCCESS;
    }
}
