<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Service\MemberService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Laravel\Jetstream\Contracts\AddsTeamMembers;

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

        app(MemberService::class)->addMember($newOrganizationMember, $organization, Role::from($role));
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
                ExistsEloquent::make(User::class, 'email', function (Builder $builder) {
                    /** @var Builder<User> $builder */
                    return $builder->where('is_placeholder', '=', false);
                })->withMessage(__('We were unable to find a registered user with this email address.')),
            ],
            'role' => [
                'required',
                'string',
                Rule::in([
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
        return function ($validator) use ($team, $email): void {
            $validator->errors()->addIf(
                $team->hasRealUserWithEmail($email),
                'email',
                __('This user already belongs to the team.')
            );
        };
    }
}
