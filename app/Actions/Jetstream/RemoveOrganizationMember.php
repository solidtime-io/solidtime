<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Events\TeamMemberRemoved;

class RemoveOrganizationMember implements RemovesTeamMembers
{
    /**
     * Remove the team member from the given team.
     */
    public function remove(User $user, Organization $organization, User $teamMember): void
    {
        $this->authorize($user, $organization, $teamMember);

        $this->ensureUserDoesNotOwnTeam($teamMember, $organization);

        $organization->removeUser($teamMember);

        TeamMemberRemoved::dispatch($organization, $teamMember);
    }

    /**
     * Authorize that the user can remove the team member.
     */
    protected function authorize(User $user, Organization $organization, User $teamMember): void
    {
        if (! Gate::forUser($user)->check('removeTeamMember', $organization) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * Ensure that the currently authenticated user does not own the team.
     */
    protected function ensureUserDoesNotOwnTeam(User $teamMember, Organization $organization): void
    {
        if ($teamMember->id === $organization->owner->id) {
            throw ValidationException::withMessages([
                'team' => [__('You may not leave a team that you created.')],
            ])->errorBag('removeTeamMember');
        }
    }
}
