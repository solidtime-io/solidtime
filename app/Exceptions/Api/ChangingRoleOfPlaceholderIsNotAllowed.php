<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class ChangingRoleOfPlaceholderIsNotAllowed extends ApiException
{
    public const string KEY = 'changing_role_of_placeholder_is_not_allowed';
}
