<?php

declare(strict_types=1);

namespace App\Console\Commands\Correction;

use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\TimeEntry;
use Illuminate\Console\Command;

class CorrectionDeleteTimeEntriesWithEndBeforeStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correction:delete-time-entries-with-end-before-start '.
        ' { --dry-run : Do not actually save anything to the database, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all time entries where the end is before the start';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('Deletes all time entries where the end is before the start...');
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. Nothing will be saved to the database.');
        }

        $timeEntries = TimeEntry::query()
            ->whereNotNull('end')
            ->whereColumn('end', '<', 'start')
            ->with(['project', 'task'])
            ->get();
        foreach ($timeEntries as $timeEntry) {
            /** @var TimeEntry $timeEntry */
            if (! $dryRun) {
                // Delete via Eloquent, so that the deletion is audited
                $timeEntry->delete();
                if ($timeEntry->project !== null) {
                    RecalculateSpentTimeForProject::dispatch($timeEntry->project);
                }
                if ($timeEntry->task !== null) {
                    RecalculateSpentTimeForTask::dispatch($timeEntry->task);
                }
            }
            $this->line('Deleted time entry (id='.$timeEntry->getKey().', start='.$timeEntry->start->toIso8601ZuluString().', end='.$timeEntry->end?->toIso8601ZuluString().')');
        }

        return self::SUCCESS;
    }
}
