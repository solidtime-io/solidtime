<?php

declare(strict_types=1);

namespace App\Service;

use App\Mail\OrganizationInvitationMail;
use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Mail;

class OrganizationInvitationService
{
    public function resend(OrganizationInvitation $invitation): void
    {
        Mail::to($invitation->email)
            ->queue(new OrganizationInvitationMail($invitation));
    }
}
