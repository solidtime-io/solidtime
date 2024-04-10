<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Service\UserService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;

class AddOrganizationMember implements AddsTeamMembers
{
    /**
     * Add a new team member to the given team.
     */
    public function add(User $owner, Organization $organization, string $email, ?string $role = null): void
    {
        Gate::forUser($owner)->authorize('addTeamMember', $organization); // TODO: refactor after owner refactoring

        $this->validate($organization, $email, $role);

        $newOrganizationMember = User::query()
            ->where('email', $email)
            ->where('is_placeholder', '=', false)
            ->firstOrFail();

        AddingTeamMember::dispatch($organization, $newOrganizationMember);

        DB::transaction(function () use ($organization, $newOrganizationMember, $role) {
            $organization->users()->attach(
                $newOrganizationMember, ['role' => $role]
            );

            if ($role === Role::Owner->value) {
                app(UserService::class)->changeOwnership($organization, $newOrganizationMember);
            }
        });

        TeamMemberAdded::dispatch($organization, $newOrganizationMember);
    }

    /**
     * Validate the add member operation.
     */
    protected function validate(Organization $organization, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], $this->rules())->after(
            $this->ensureUserIsNotAlreadyOnTeam($organization, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for adding a team member.
     *
     * @return array<string, array<ValidationRule|Rule|string|In>>
     */
    protected function rules(): array
    {
        return array_filter([
            'email' => [
                'required',
                'email',
                (new ExistsEloquent(User::class, 'email', function (Builder $builder) {
                    return $builder->where('is_placeholder', '=', false);
                }))->withMessage(__('We were unable to find a registered user with this email address.')),
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
    protected function ensureUserIsNotAlreadyOnTeam(Organization $team, string $email): Closure
    {
        return function ($validator) use ($team, $email) {
            $validator->errors()->addIf(
                $team->hasRealUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }
}
