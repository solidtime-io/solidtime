<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class UserNotPlaceholderApiException extends ApiException
{
    public const string KEY = 'user_not_placeholder';
}
