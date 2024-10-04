<?php

declare(strict_types=1);

namespace App\Console\Commands\SelfHost;

use App\Service\ApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SelfHostCheckForUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'self-host:check-for-update';

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

        $latestVersion = $apiService->checkForUpdate();
        if ($latestVersion === null) {
            $this->error('Failed to check for update, check the logs for more information.');

            return self::FAILURE;
        }

        // Note: Cache for 13 hours, because the command runs twice daily (every 12 hours).
        Cache::put('latest_version', $latestVersion, 60 * 60 * 12);

        return self::SUCCESS;
    }
}
