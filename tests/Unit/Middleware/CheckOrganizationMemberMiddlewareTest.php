<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckOrganizationMember;
use App\Models\Organization;
use App\Service\PermissionStore;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CheckOrganizationMember::class)]
class CheckOrganizationMemberMiddlewareTest extends MiddlewareTestAbstract
{
    private function createTestRoute(): void
    {
        Route::get('/test-route/{organization}', function (Organization $organization) {
            return response()->json(['message' => 'Test route', 'id' => $organization->getKey()]);
        })->middleware([SubstituteBindings::class, CheckOrganizationMember::class]);
    }

    public function test_request_fails_if_user_is_not_a_member_of_the_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $otherOrganization = Organization::factory()->create();
        $this->createTestRoute();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson('/test-route/'.$otherOrganization->getKey());

        // Assert
        $response->assertForbidden();
    }

    public function test_request_fails_if_organization_is_not_found(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->createTestRoute();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson('/test-route/'.Str::uuid());

        // Assert
        $response->assertNotFound();
    }

    public function test_request_succeeds_if_user_is_a_member_of_the_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->createTestRoute();
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson('/test-route/'.$user->organization->getKey());

        // Assert
        $response->assertOk();
        $response->assertJson(['message' => 'Test route', 'id' => $user->organization->getKey()]);
    }

    public function test_request_succeeds_on_route_without_organization_parameter(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        Route::get('/test-route', function () {
            return response()->json(['message' => 'Test route']);
        })->middleware([SubstituteBindings::class, CheckOrganizationMember::class]);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson('/test-route');

        // Assert
        $response->assertOk();
    }

    public function test_membership_check_warms_the_permission_cache(): void
    {
        // Arrange
        $user = $this->createUserWithPermission(['time-entries:view:own']);
        Route::get('/test-route/{organization}', function (Organization $organization) {
            DB::enableQueryLog();
            $hasPermission = app(PermissionStore::class)->has($organization, 'time-entries:view:own');

            return response()->json(['has_permission' => $hasPermission, 'queries' => count(DB::getQueryLog())]);
        })->middleware([SubstituteBindings::class, CheckOrganizationMember::class]);
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson('/test-route/'.$user->organization->getKey());

        // Assert
        $response->assertOk();
        $response->assertJson(['has_permission' => true, 'queries' => 0]);
    }

    public function test_api_endpoint_returns_forbidden_for_non_member_without_logging_an_error(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $otherOrganization = Organization::factory()->create();
        Passport::actingAs($user->user);
        Log::spy();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [$otherOrganization->getKey()]));

        // Assert
        $response->assertForbidden();
        Log::shouldNotHaveReceived('error');
    }
}
