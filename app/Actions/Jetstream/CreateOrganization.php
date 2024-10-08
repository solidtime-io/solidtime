<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Events\AfterCreateOrganization;
use App\Models\Organization;
use App\Models\User;
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
     */
    public function create(User $user, array $input): Organization
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        $organization = new Organization;
        $organization->name = $input['name'];
        $organization->personal_team = false;
        $organization->owner()->associate($user);
        $organization->save();

        $organization->users()->attach(
            $user, [
                'role' => Role::Owner->value,
            ]
        );

        $user->switchTeam($organization);

        // Note: The refresh is necessary for currently unknown reasons. Do not remove it.
        $organization = $organization->refresh();
        AfterCreateOrganization::dispatch($organization);

        return $organization;
    }
}
