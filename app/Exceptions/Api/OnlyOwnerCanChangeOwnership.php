<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class OnlyOwnerCanChangeOwnership extends ApiException
{
    public const string KEY = 'only_owner_can_change_ownership';
}
