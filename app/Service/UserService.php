<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\Role;
use App\Enums\TimeFormat;
use App\Enums\Weekday;
use App\Events\AfterCreateOrganization;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(
        string $name,
        string $email,
        string $password,
        string $timezone,
        Weekday $weekStart,
        ?string $currency,
        ?NumberFormat $numberFormat = null,
        ?CurrencyFormat $currencyFormat = null,
        ?DateFormat $dateFormat = null,
        ?IntervalFormat $intervalFormat = null,
        ?TimeFormat $timeFormat = null,
        bool $verifyEmail = false
    ): User {
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->timezone = $timezone;
        $user->week_start = $weekStart;
        if ($verifyEmail) {
            $user->email_verified_at = Carbon::now();
        }
        $user->save();

        $organization = app(OrganizationService::class)->createOrganization(
            $this->getOrganizationNameForUserName($user->name),
            $user,
            true,
            $currency,
            $numberFormat,
            $currencyFormat,
            $dateFormat,
            $intervalFormat,
            $timeFormat,
        );

        $user->ownedTeams()->save($organization);

        return $user;
    }

    /**
     * This does NOT change the member id.
     * This should only be used in if you want to change a member to a placeholder!
     */
    public function assignOrganizationEntitiesToDifferentUser(Organization $organization, User $fromUser, User $toUser): void
    {
        // Time entries
        TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
            ]);

        // Project members
        ProjectMember::query()
            ->whereBelongsToOrganization($organization)
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
            ]);
    }

    public function makeSureUserHasAtLeastOneOrganization(User $user): void
    {
        if ($user->organizations()->count() > 0) {
            return;
        }

        // Create a new organization
        $organization = app(OrganizationService::class)->createOrganization(
            $this->getOrganizationNameForUserName($user->name),
            $user,
            true
        );

        // Set the organization as the user's current organization
        $user->currentOrganization()->associate($organization);
        $user->save();

        AfterCreateOrganization::dispatch($organization);
    }

    public function getOrganizationNameForUserName(string $username): string
    {
        return explode(' ', $username, 2)[0]."'s Organization";
    }

    public function makeSureUserHasCurrentOrganization(User $user): void
    {
        if ($user->currentOrganization !== null) {
            return;
        }

        $organization = $user->organizations()->first();
        $user->currentOrganization()->associate($organization);
        $user->save();
    }

    /**
     * Change the ownership of an organization to a new user.
     * The previous owner will be demoted to an admin.
     */
    public function changeOwnership(Organization $organization, User $newOwner): void
    {
        $organization->update([
            'user_id' => $newOwner->getKey(),
        ]);
        /** @var Member|null $userMembership */
        $userMembership = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($newOwner, 'user')
            ->first();
        if ($userMembership === null) {
            throw new \InvalidArgumentException('User is not a member of the organization');
        }
        $userMembership->role = Role::Owner->value;
        $userMembership->save();
        $oldOwners = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->where('role', '=', Role::Owner->value)
            ->where('user_id', '!=', $newOwner->getKey())
            ->get();
        foreach ($oldOwners as $oldOwner) {
            $oldOwner->role = Role::Admin->value;
            $oldOwner->save();
        }
    }
}
