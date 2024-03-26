<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class ApiEndpointTestAbstract extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string>  $permissions
     * @return object{user: User, organization: Organization}
     */
    protected function createUserWithPermission(array $permissions, bool $isOwner = false): object
    {
        Jetstream::role('custom-test', 'Custom Test', $permissions)
            ->description('Role custom for testing');
        $user = User::factory()->create();
        if ($isOwner) {
            $organization = Organization::factory()->withOwner($user)->create();
        } else {
            $organization = Organization::factory()->create();
        }
        $organization->users()->attach($user, [
            'role' => 'custom-test',
        ]);

        return (object) [
            'user' => $user,
            'organization' => $organization,
        ];
    }
}
