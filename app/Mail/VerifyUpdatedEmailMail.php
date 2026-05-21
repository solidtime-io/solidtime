<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class VerifyUpdatedEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $email;

    public function __construct(User $user, string $email)
    {
        $this->user = $user;
        $this->email = Str::lower($email);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'user' => $this->user->getKey(),
                'email' => $this->email,
            ],
            false
        );

        return $this->markdown('emails.verify-updated-email', [
            'verificationUrl' => URL::to($verificationUrl),
        ])->subject(__('Verify Email Address'));
    }
}
