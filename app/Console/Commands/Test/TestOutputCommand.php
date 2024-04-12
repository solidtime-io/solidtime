<?php

declare(strict_types=1);

namespace App\Console\Commands\Test;

use Illuminate\Console\Command;

class TestOutputCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:output';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This test command outputs some text.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Test command output');
        $this->error('Test command output error');

        return self::SUCCESS;
    }
}
