<?php

declare(strict_types=1);

namespace Extensions\Linear\Console;

use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Console\Command;

class SyncLinearCommand extends Command
{
    protected $signature = 'linear:sync';

    protected $description = 'Sync Linear issues for all connected users';

    public function handle(): int
    {
        $integrations = LinearIntegration::all();

        foreach ($integrations as $integration) {
            SyncLinearIssuesForUser::dispatch($integration);
        }

        $this->info("Dispatched sync jobs for {$integrations->count()} integration(s).");

        return self::SUCCESS;
    }
}
