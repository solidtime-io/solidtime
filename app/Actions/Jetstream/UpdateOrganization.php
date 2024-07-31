<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\User;
use App\Rules\CurrencyRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;

class UpdateOrganization implements UpdatesTeamNames
{
    /**
     * Validate and update the given team's name.
     *
     * @param  array<string, string>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $user, Organization $organization, array $input): void
    {
        Gate::forUser($user)->authorize('update', $organization);

        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'currency' => [
                'required',
                'string',
                new CurrencyRule,
            ],
        ])->validateWithBag('updateTeamName');

        $organization->forceFill([
            'name' => $input['name'],
            'currency' => $input['currency'],
        ])->save();
    }
}
