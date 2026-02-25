<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Events\AfterCreateOrganization;
use App\Models\Organization;
use App\Models\User;
use App\Service\IpLookup\IpLookupServiceContract;
use App\Service\OrganizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Jetstream;

class CreateOrganization implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     *
     * @deprecated Use REST endpoint instead
     */
    public function create(User $user, array $input): Organization
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        $ipLookupResponse = app(IpLookupServiceContract::class)->lookup(request()->ip());

        $currency = null;
        if ($ipLookupResponse !== null) {
            $currency = $ipLookupResponse->currency;
        }

        $organization = app(OrganizationService::class)->createOrganization(
            $input['name'],
            $user,
            false,
            $currency
        );

        $user->switchTeam($organization);

        // Note: The refresh is necessary for currently unknown reasons. Do not remove it.
        $organization = $organization->refresh();
        AfterCreateOrganization::dispatch($organization);

        return $organization;
    }
}
