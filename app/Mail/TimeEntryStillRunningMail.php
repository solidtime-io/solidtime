<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TimeEntryStillRunningMail extends Mailable
{
    use Queueable, SerializesModels;

    public TimeEntry $timeEntry;

    public User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(TimeEntry $timeEntry, User $user)
    {
        $this->timeEntry = $timeEntry;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.time-entry-still-running', [
            'dashboardUrl' => URL::route('dashboard'),
        ])
            ->subject(__('Your Time Tracker is still running!'));
    }
}
