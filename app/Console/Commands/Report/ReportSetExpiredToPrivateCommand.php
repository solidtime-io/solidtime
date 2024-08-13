<?php

declare(strict_types=1);

namespace App\Console\Commands\Report;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ReportSetExpiredToPrivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:set-expired-to-private '.
        ' { --dry-run : Do not actually save anything to the database, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes public reports private if the public_until date has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('Makes public reports private if the public_until date has passed...');
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. Nothing will be saved to the database.');
        }

        $resetReports = 0;
        Report::query()
            ->where('public_until', '<', Carbon::now())
            ->orderBy('created_at', 'asc')
            ->chunk(500, function (Collection $reports) use ($dryRun, &$resetReports): void {
                /** @var Collection<int, Report> $reports */
                foreach ($reports as $report) {
                    $this->info('Make report "'.$report->name.'" ('.$report->getKey().') private, expired: '.$report->public_until->toIso8601ZuluString().' ('.$report->public_until->diffForHumans().')');
                    $resetReports++;
                    if (! $dryRun) {
                        $report->is_public = false;
                        $report->share_secret = null;
                        $report->save();
                    }
                }
            });

        $this->comment('Finished setting '.$resetReports.' expired reports to private...');

        return self::SUCCESS;
    }
}
