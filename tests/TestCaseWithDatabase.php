<?php

declare(strict_types=1);

namespace Tests;

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
     * @return object{user: User, organization: Organization, member: Member}
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
            $organization = Organization::factory()->create();
        }
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create([
            'role' => $roleName,
        ]);

        return (object) [
            'user' => $user,
            'organization' => $organization,
            'member' => $member,
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
