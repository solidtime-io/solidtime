<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrganizationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrganizationInvitation $invitation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(OrganizationInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.organization-invitation', [
            'acceptUrl' => URL::signedRoute('team-invitations.accept', [
                'invitation' => $this->invitation,
            ]),
        ])->subject(__('Organization Invitation'));
    }
}
