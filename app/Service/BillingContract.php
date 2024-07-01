<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;

class BillingContract
{
    public function hasSubscription(Organization $organization): bool
    {
        return false;
    }
}
