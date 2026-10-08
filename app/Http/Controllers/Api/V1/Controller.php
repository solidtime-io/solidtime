<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Exceptions\Api\FeatureIsNotAvailableInFreePlanApiException;
use App\Models\Organization;
use App\Service\BillingContract;
use App\Service\PermissionStore;
use Illuminate\Auth\Access\AuthorizationException;

class Controller extends \App\Http\Controllers\Controller
{
    public function __construct(
        protected PermissionStore $permissionStore,
    ) {}

    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission): void
    {
        if (! $this->permissionStore->has($organization, $permission)) {
            throw new AuthorizationException;
        }
    }

    /**
     * @param  array<string>  $permissions
     *
     * @throws AuthorizationException
     */
    protected function checkAnyPermission(Organization $organization, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($this->permissionStore->has($organization, $permission)) {
                return;
            }
        }
        throw new AuthorizationException;
    }

    protected function hasPermission(Organization $organization, string $permission): bool
    {
        return $this->permissionStore->has($organization, $permission);
    }

    protected function canAccessPremiumFeatures(Organization $organization): bool
    {
        return app(BillingContract::class)->hasSubscription($organization) || app(BillingContract::class)->hasTrial($organization);
    }

    protected function canUseBillableRates(Organization $organization): bool
    {
        return app(BillingContract::class)->canUseBillableRates($organization);
    }

    protected function canSeeBillableRates(Organization $organization): bool
    {
        if (! $this->canUseBillableRates($organization)) {
            return false;
        }

        return $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;
    }

    /**
     * Organizations that can not use billable rates may keep or remove an existing rate, but not set a new one
     *
     * @throws FeatureIsNotAvailableInFreePlanApiException
     */
    protected function checkBillableRateChange(Organization $organization, ?int $oldBillableRate, ?int $newBillableRate): void
    {
        if ($newBillableRate !== null && $newBillableRate !== $oldBillableRate && ! $this->canUseBillableRates($organization)) {
            throw new FeatureIsNotAvailableInFreePlanApiException;
        }
    }
}
