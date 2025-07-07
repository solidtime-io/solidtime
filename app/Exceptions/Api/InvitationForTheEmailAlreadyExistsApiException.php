<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class InvitationForTheEmailAlreadyExistsApiException extends ApiException
{
    public const string KEY = 'invitation_for_the_email_already_exists';
}
