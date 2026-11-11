<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AuthApiTokenCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Token $token;

    public User $user;

    public function __construct(Token $token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.auth-api-token-created', [
            'profileUrl' => URL::to('user/profile'),
            'tokenName' => $this->token->name,
        ])
            ->subject(__('A new API token was created for your account'));
    }
}
