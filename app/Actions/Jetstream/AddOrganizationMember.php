<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

class AddOrganizationMember implements AddsTeamMembers
{
    /**
     * Add a new team member to the given team.
     */
    public function add(User $user, Organization $organization, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $organization);

        $this->validate($organization, $email, $role);

        $newTeamMember = Jetstream::findUserByEmailOrFail($email);

        AddingTeamMember::dispatch($organization, $newTeamMember);

        $organization->users()->attach(
            $newTeamMember, ['role' => $role]
        );

        TeamMemberAdded::dispatch($organization, $newTeamMember);
    }

    /**
     * Validate the add member operation.
     */
    protected function validate(Organization $organization, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules(), [
            'email.exists' => __('We were unable to find a registered user with this email address.'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($organization, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for adding a team member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function rules(): array
    {
        return array_filter([
            'email' => ['required', 'email', 'exists:users'],
            'role' => Jetstream::hasRoles()
                            ? ['required', 'string', new Role]
                            : null,
        ]);
    }

    /**
     * Ensure that the user is not already on the team.
     */
    protected function ensureUserIsNotAlreadyOnTeam(Organization $team, string $email): Closure
    {
        return function ($validator) use ($team, $email) {
            $validator->errors()->addIf(
                $team->hasUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }
}
