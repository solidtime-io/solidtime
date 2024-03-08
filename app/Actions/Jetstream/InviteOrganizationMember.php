<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Mail\TeamInvitation;
use Laravel\Jetstream\Rules\Role;

class InviteOrganizationMember implements InvitesTeamMembers
{
    /**
     * Invite a new team member to the given team.
     */
    public function invite(User $user, Organization $organization, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $organization);

        $this->validate($organization, $email, $role);

        InvitingTeamMember::dispatch($organization, $email, $role);

        /** @var TeamInvitation $invitation */
        $invitation = $organization->teamInvitations()->create([
            'email' => $email,
            'role' => $role,
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));
    }

    /**
     * Validate the invite member operation.
     */
    protected function validate(Organization $organization, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules($organization))->after(
            $this->ensureUserIsNotAlreadyOnTeam($organization, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for inviting a team member.
     *
     * @return array<string, array<ValidationRule|Rule|string>>
     */
    protected function rules(Organization $organization): array
    {
        return array_filter([
            'email' => [
                'required',
                'email',
                (new UniqueEloquent(OrganizationInvitation::class, 'email', function (Builder $builder) use ($organization) {
                    /** @var Builder<OrganizationInvitation> $builder */
                    return $builder->whereBelongsTo($organization, 'organization');
                }))->withMessage(__('This user has already been invited to the team.')),
            ],
            'role' => Jetstream::hasRoles()
                            ? ['required', 'string', new Role]
                            : null,
        ]);
    }

    /**
     * Ensure that the user is not already on the team.
     */
    protected function ensureUserIsNotAlreadyOnTeam(Organization $organization, string $email): Closure
    {
        return function ($validator) use ($organization, $email) {
            $validator->errors()->addIf(
                $organization->hasRealUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }
}
