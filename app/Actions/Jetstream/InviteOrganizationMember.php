<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\PermissionStore;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Mail\TeamInvitation;

class InviteOrganizationMember implements InvitesTeamMembers
{
    /**
     * Invite a new team member to the given team.
     *
     * @throws AuthorizationException
     */
    public function invite(User $user, Organization $organization, string $email, ?string $role = null): void
    {
        if (! app(PermissionStore::class)->has($organization, 'invitations:create')) {
            throw new AuthorizationException();
        }

        $this->validate($organization, $email, $role);

        InvitingTeamMember::dispatch($organization, $email, $role);

        /** @var OrganizationInvitation $invitation */
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
     * @return array<string, array<ValidationRule|Rule|string|In>>
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
            'role' => [
                'required',
                'string',
                Rule::in([
                    Role::Owner->value,
                    Role::Admin->value,
                    Role::Manager->value,
                    Role::Employee->value,
                ]),
            ],
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
