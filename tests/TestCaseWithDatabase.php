<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;

abstract class TestCaseWithDatabase extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string>  $permissions
     * @return object{user: User, organization: Organization, member: Member, owner: User, ownerMember: Member}
     */
    protected function createUserWithPermission(array $permissions = [], bool $isOwner = false): object
    {
        $roleName = 'custom-test-'.Str::uuid();
        Jetstream::role($roleName, 'Custom Test', $permissions)
            ->description('Role custom for testing');
        $user = User::factory()->create();
        if ($isOwner) {
            $organization = Organization::factory()->withOwner($user)->create();
        } else {
            $owner = User::factory()->create();
            $organization = Organization::factory()->withOwner($owner)->create();
            $ownerMember = Member::factory()->forUser($owner)->forOrganization($organization)->create([
                'role' => Role::Owner->value,
            ]);
            $owner->currentOrganization()->associate($organization);
            $owner->save();
        }
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create([
            'role' => $roleName,
        ]);
        $user->currentOrganization()->associate($organization);
        $user->save();

        return (object) [
            'user' => $user,
            'organization' => $organization,
            'member' => $member,
            'owner' => $isOwner ? $user : $owner,
            'ownerMember' => $isOwner ? $member : $ownerMember,
        ];
    }

    /**
     * @return object{user: User, organization: Organization, member: Member, owner: User, ownerMember: Member}
     */
    public function createUserWithRole(Role $role, bool $employeesCanSeeBillableRates = false): object
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create([
            'employees_can_see_billable_rates' => $employeesCanSeeBillableRates,
        ]);
        $ownerMember = Member::factory()->forUser($owner)->forOrganization($organization)->role(Role::Owner)->create();
        $owner->currentOrganization()->associate($organization);
        $owner->save();

        if ($role === Role::Owner) {
            $user = $owner;
            $member = $ownerMember;
        } else {
            $user = User::factory()->create();
            $member = Member::factory()->forUser($user)->forOrganization($organization)->role($role)->create();
            $user->currentOrganization()->associate($organization);
        }

        return (object) [
            'user' => $user,
            'organization' => $organization,
            'member' => $member,
            'owner' => $owner,
            'ownerMember' => $ownerMember,
        ];
    }

    protected function enableQueryLog(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    protected function getQueryLog(): array
    {
        if (! DB::logging()) {
            throw new \LogicException('Query log is not enabled. Call enableQueryLog() before calling getQueryLog()');
        }

        return DB::getQueryLog();
    }

    protected function assertQueryCount(int $count, string $message = ''): void
    {
        $queryLog = $this->getQueryLog();
        $this->assertCount($count, $queryLog, $message);
    }
}
