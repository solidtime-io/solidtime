<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\TimeEntry;

use App\Console\Commands\TimeEntry\TimeEntrySendStillRunningMailsCommand;
use App\Mail\TimeEntryStillRunningMail;
use App\Models\TimeEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntrySendStillRunningMailsCommand::class)]
#[UsesClass(TimeEntrySendStillRunningMailsCommand::class)]
class TimeEntrySendStillRunningMailsCommandTest extends TestCaseWithDatabase
{
    public function test_sends_mails_for_still_running_time_entries(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntryRunningLongerThanThreshold = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->subSecond(),
            'end' => null,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails');

        // Assert
        Mail::assertQueued(TimeEntryStillRunningMail::class, function ($mail) use ($user, $timeEntryRunningLongerThanThreshold) {
            return $mail->hasTo($user->user->email) &&
                $mail->timeEntry->is($timeEntryRunningLongerThanThreshold) &&
                $mail->user->is($user->user);
        });
        $timeEntryRunningLongerThanThreshold->refresh();
        $this->assertNotNull($timeEntryRunningLongerThanThreshold->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            'Start sending email to user "'.$user->user->email.'" ('.$user->user->getKey().') for time entry '.$timeEntryRunningLongerThanThreshold->getKey()."\n".
            "Finished sending 1 still running time entry emails...\n", $output);

    }

    public function test_does_not_send_emails_for_not_running_time_entries(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->subSecond(),
            'end' => Carbon::now(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails');

        // Assert
        Mail::assertNothingOutgoing();
        $timeEntry->refresh();
        $this->assertNull($timeEntry->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            "Finished sending 0 still running time entry emails...\n", $output);
    }

    public function test_does_not_send_emails_for_running_time_entries_that_are_short_than_the_threshold(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->addMinute(),
            'end' => null,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails');

        // Assert
        Mail::assertNothingOutgoing();
        $timeEntry->refresh();
        $this->assertNull($timeEntry->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            "Finished sending 0 still running time entry emails...\n", $output);
    }

    public function test_does_not_send_emails_for_running_time_entries_that_are_longer_than_the_threshold_but_already_received_the_email(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->subMinute(),
            'end' => null,
            'still_active_email_sent_at' => Carbon::now()->subMinute(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails');

        // Assert
        Mail::assertNothingOutgoing();
        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            "Finished sending 0 still running time entry emails...\n", $output);
    }

    public function test_dry_run_option_does_not_send_mails_but_outputs_what_would_happen(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntryRunningLongerThanThreshold = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->subSecond(),
            'end' => null,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails --dry-run');

        // Assert
        Mail::assertNothingOutgoing();
        $timeEntryRunningLongerThanThreshold->refresh();
        $this->assertNull($timeEntryRunningLongerThanThreshold->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            "Running in dry-run mode. No emails will be sent and nothing will be saved to the database.\n".
            'Start sending email to user "'.$user->user->email.'" ('.$user->user->getKey().') for time entry '.$timeEntryRunningLongerThanThreshold->getKey()."\n".
            "Finished sending 1 still running time entry emails...\n", $output);
    }

    public function test_does_not_send_emails_for_placeholder_users(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $user->user->is_placeholder = true;
        $user->user->save();
        $timeEntryRunningLongerThanThreshold = TimeEntry::factory()->forMember($user->member)->create([
            'start' => Carbon::now()->subHours(8)->subSecond(),
            'end' => null,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('time-entry:send-still-running-mails');

        // Assert
        Mail::assertNothingOutgoing();
        $timeEntryRunningLongerThanThreshold->refresh();
        $this->assertNull($timeEntryRunningLongerThanThreshold->still_active_email_sent_at);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Sending still running time entry emails...\n".
            "Finished sending 0 still running time entry emails...\n", $output);
    }
}
