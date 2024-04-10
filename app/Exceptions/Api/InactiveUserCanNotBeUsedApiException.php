<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class InactiveUserCanNotBeUsedApiException extends ApiException
{
    public const string KEY = 'inactive_user_can_not_be_used';
}
