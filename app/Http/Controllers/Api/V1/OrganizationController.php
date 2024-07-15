<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Organization\OrganizationUpdateRequest;
use App\Http\Resources\V1\Organization\OrganizationResource;
use App\Models\Organization;
use App\Service\BillableRateService;
use Illuminate\Auth\Access\AuthorizationException;

class OrganizationController extends Controller
{
    /**
     * Get organization
     *
     * @operationId getOrganization
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization): OrganizationResource
    {
        $this->checkPermission($organization, 'organizations:view');

        return new OrganizationResource($organization);
    }

    /**
     * Update organization
     *
     * @operationId updateOrganization
     *
     * @throws AuthorizationException
     */
    public function update(Organization $organization, OrganizationUpdateRequest $request, BillableRateService $billableRateService): OrganizationResource
    {
        $this->checkPermission($organization, 'organizations:update');

        $organization->name = $request->input('name');
        $oldBillableRate = $organization->billable_rate;
        $organization->billable_rate = $request->getBillableRate();
        $organization->save();

        if ($oldBillableRate !== $request->getBillableRate()) {
            $billableRateService->updateTimeEntriesBillableRateForOrganization($organization);
        }

        return new OrganizationResource($organization);
    }
}
