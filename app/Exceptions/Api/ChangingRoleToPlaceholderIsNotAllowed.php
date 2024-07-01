<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class ChangingRoleToPlaceholderIsNotAllowed extends ApiException
{
    public const string KEY = 'changing_role_to_placeholder_is_not_allowed';
}
