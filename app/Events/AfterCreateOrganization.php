<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * This event is fired after an organization has been created.
 * This event does NOT fire when an organization is created as part of a registration.
 */
class AfterCreateOrganization
{
    use Dispatchable;

    public Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }
}
