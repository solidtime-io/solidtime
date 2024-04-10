<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use App\Service\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\TeamMemberUpdated;

class UpdateMemberRole
{
    /**
     * Update the role for the given team member.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $actingUser, Organization $organization, string $userId, string $role): void
    {
        if (! app(PermissionStore::class)->has($organization, 'members:change-role')) {
            throw new AuthorizationException();
        }

        $user = User::where('id', '=', $userId)->firstOrFail();
        $member = Membership::whereBelongsTo($user)->whereBelongsTo($organization)->firstOrFail();
        if ($member->role === Role::Placeholder->value) {
            abort(403, 'Cannot update the role of a placeholder member.');
        }

        Validator::make([
            'role' => $role,
        ], [
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
        ])->validate();

        DB::transaction(function () use ($organization, $userId, $role, $user) {
            $organization->users()->updateExistingPivot($userId, [
                'role' => $role,
            ]);

            if ($role === Role::Owner->value) {
                app(UserService::class)->changeOwnership($organization, $user);
            }
        });

        TeamMemberUpdated::dispatch($organization->fresh(), User::findOrFail($userId));
    }
}
