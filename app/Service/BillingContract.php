<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;
use Illuminate\Support\Carbon;

/**
 * This class is a contract for the billing system
 * The billing system is responsible for managing the subscriptions of organizations
 * The concrete implementation of this contract for the cloud version of solidtime is implemented in an extension
 */
class BillingContract
{
    /**
     * Check if the organization has a Professional subscription
     * A Professional subscription is a paid subscription that allows the organization to:
     *  - Have more than 1 non-placeholder member
     *  - Access features that are not available to free organizations
     */
    public function hasSubscription(Organization $organization): bool
    {
        return false;
    }

    /**
     * Check if the organization has a trial subscription
     * A trial subscription gives the organization the same benefits as a Professional subscription, but for a limited time
     */
    public function hasTrial(Organization $organization): bool
    {
        return false;
    }

    /**
     * Get the date until which the organization's trial subscription is valid
     * If the organization does not have a trial subscription, this method should return null
     */
    public function getTrialUntil(Organization $organization): ?Carbon
    {
        return null;
    }

    /**
     * Check if the organization is blocked
     * A blocked organization is an organization that has more than 1 non-placeholder member but no subscription/trial
     * This can happen if:
     *  - The organization's trial has expired and during the trial the organization added non-placeholder members
     *  - The organization's subscription has expired and the organization has more than 1 non-placeholder member
     */
    public function isBlocked(Organization $organization): bool
    {
        return false;
    }
}
