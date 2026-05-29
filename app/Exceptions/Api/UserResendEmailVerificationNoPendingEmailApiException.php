<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class UserResendEmailVerificationNoPendingEmailApiException extends ApiException
{
    public const string KEY = 'user_resend_email_verification_no_pending_email';
}
