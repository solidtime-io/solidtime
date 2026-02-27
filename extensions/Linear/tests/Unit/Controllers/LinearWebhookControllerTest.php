<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Controllers;

use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Jobs\ProcessLinearWebhook;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Queue;
use Tests\TestCaseWithDatabase;

class LinearWebhookControllerTest extends TestCaseWithDatabase
{
    public function test_valid_webhook_dispatches_job_and_returns_200(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $secret = 'test-webhook-secret';

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
            'webhook_secret' => $secret,
        ]);

        $payload = json_encode([
            'action' => 'create',
            'type' => 'Issue',
            'data' => [
                'id' => 'issue-1',
                'title' => 'Test issue',
                'assignee' => ['id' => 'linear-user-123'],
                'state' => ['type' => 'started'],
                'project' => null,
                'estimate' => null,
            ],
            'webhookTimestamp' => now()->getTimestampMs(),
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call(
            'POST',
            route('api.v1.linear.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Linear-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(200);
        Queue::assertPushed(ProcessLinearWebhook::class);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
            'webhook_secret' => 'real-secret',
        ]);

        $payload = json_encode([
            'action' => 'create',
            'type' => 'Issue',
            'data' => [],
            'webhookTimestamp' => now()->getTimestampMs(),
        ]);

        $response = $this->call(
            'POST',
            route('api.v1.linear.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Linear-Signature' => 'bad-signature',
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(401);
    }

    public function test_missing_signature_returns_401(): void
    {
        $response = $this->postJson(
            route('api.v1.linear.webhook'),
            ['type' => 'Issue', 'action' => 'create', 'data' => []]
        );

        $response->assertStatus(401);
    }

    public function test_non_issue_events_are_ignored(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $secret = 'test-secret';

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
            'webhook_secret' => $secret,
        ]);

        $payload = json_encode([
            'action' => 'create',
            'type' => 'Comment',
            'data' => ['id' => 'comment-1'],
            'webhookTimestamp' => now()->getTimestampMs(),
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call(
            'POST',
            route('api.v1.linear.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Linear-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(200);
        Queue::assertNotPushed(ProcessLinearWebhook::class);
    }
}
