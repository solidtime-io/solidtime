<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Models;

use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Models\LinearIntegration;
use Tests\TestCaseWithDatabase;

class LinearIntegrationModelTest extends TestCaseWithDatabase
{
    public function test_it_encrypts_tokens(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        // Act
        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
        ]);

        // Assert
        $integration->refresh();
        $this->assertEquals('test-access-token', $integration->access_token);
        $this->assertEquals('test-refresh-token', $integration->refresh_token);
        // Raw DB value should be encrypted (not plaintext)
        $raw = \DB::table('linear_integrations')->where('id', $integration->id)->first();
        $this->assertNotEquals('test-access-token', $raw->access_token);
    }

    public function test_it_belongs_to_user_and_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
        ]);

        $this->assertTrue($integration->user->is($user));
        $this->assertTrue($integration->organization->is($organization));
    }

    public function test_is_token_expired_returns_true_when_expired(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->subMinutes(10),
            'linear_user_id' => 'linear-user-123',
        ]);

        $this->assertTrue($integration->isTokenExpired());
    }
}
