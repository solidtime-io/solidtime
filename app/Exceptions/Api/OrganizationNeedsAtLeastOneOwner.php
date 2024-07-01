<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class OrganizationNeedsAtLeastOneOwner extends ApiException
{
    public const string KEY = 'organization_needs_at_least_one_owner';
}
