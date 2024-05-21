<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class CanNotRemoveOwnerFromOrganization extends ApiException
{
    public const string KEY = 'can_not_remove_owner_from_organization';
}
