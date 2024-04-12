<?php

declare(strict_types=1);

namespace App\Console\Commands\Test;

use App\Jobs\Test\TestJob;
use App\Models\User;
use Illuminate\Console\Command;

class TestJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This test command start an async job.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::first();
        TestJob::dispatch($user, 'Test job message.');

        return self::SUCCESS;
    }
}
