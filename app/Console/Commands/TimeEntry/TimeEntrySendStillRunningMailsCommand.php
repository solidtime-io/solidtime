<?php

declare(strict_types=1);

namespace App\Console\Commands\TimeEntry;

use App\Mail\TimeEntryStillRunningMail;
use App\Models\TimeEntry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TimeEntrySendStillRunningMailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-entry:send-still-running-mails '.
        ' { --dry-run : Do not actually send emails or save anything to the database, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends emails to users who have running time entries for more than 8 hours.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('Sending still running time entry emails...');
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. No emails will be sent and nothing will be saved to the database.');
        }

        $sentMails = 0;
        TimeEntry::query()
            ->whereNull('end')
            ->where('start', '<', now()->subHours(8))
            ->whereNull('still_active_email_sent_at')
            ->with([
                'user',
            ])
            ->orderBy('created_at', 'asc')
            ->chunk(500, function (Collection $timeEntries) use ($dryRun, &$sentMails) {
                /** @var Collection<int, TimeEntry> $timeEntries */
                foreach ($timeEntries as $timeEntry) {
                    $user = $timeEntry->user;
                    $this->info('Start sending email to user "'.$user->email.'" ('.$user->getKey().') for time entry '.$timeEntry->getKey());
                    $sentMails++;
                    if (! $dryRun) {
                        Mail::to($user->email)
                            ->queue(new TimeEntryStillRunningMail($timeEntry, $user));
                        $timeEntry->still_active_email_sent_at = Carbon::now();
                        $timeEntry->save();
                    }
                }
            });

        $this->comment('Finished sending '.$sentMails.' still running time entry emails...');

        return self::SUCCESS;
    }
}
