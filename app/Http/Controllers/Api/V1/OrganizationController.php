<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Organization\OrganizationUpdateRequest;
use App\Http\Resources\V1\Organization\OrganizationResource;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;

class OrganizationController extends Controller
{
    /**
     * Get organization
     *
     * @operationId getOrganization
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
     * @throws AuthorizationException
     */
    public function update(Organization $organization, OrganizationUpdateRequest $request): OrganizationResource
    {
        $this->checkPermission($organization, 'organizations:update');

        $organization->name = $request->input('name');
        $organization->billable_rate = $request->input('billable_rate');
        $organization->save();

        return new OrganizationResource($organization);
    }
}
