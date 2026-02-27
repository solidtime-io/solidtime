<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Extensions\Linear\Jobs\ProcessLinearWebhook;
use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDatabase;

class LinearSyncIntegrationTest extends TestCaseWithDatabase
{
    public function test_full_sync_round_trip_creates_and_updates_tasks(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'lu-1',
            'last_synced_at' => null,
        ]);

        // Both syncs use a sequence: first returns Issue One, second returns updated version
        Http::fake([
            'api.linear.app/graphql' => Http::sequence()
                ->push([
                    'data' => [
                        'issues' => [
                            'nodes' => [
                                [
                                    'id' => 'i-1',
                                    'title' => 'Issue One',
                                    'state' => ['type' => 'started'],
                                    'project' => null,
                                    'estimate' => null,
                                    'updatedAt' => now()->toIso8601String(),
                                ],
                            ],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'issues' => [
                            'nodes' => [
                                [
                                    'id' => 'i-1',
                                    'title' => 'Issue One Updated',
                                    'state' => ['type' => 'completed'],
                                    'project' => null,
                                    'estimate' => null,
                                    'updatedAt' => now()->toIso8601String(),
                                ],
                            ],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ]),
        ]);

        // First sync: create issues via polling
        (new SyncLinearIssuesForUser($integration))->handle();

        $this->assertDatabaseHas('tasks', ['linear_id' => 'i-1', 'name' => 'Issue One']);

        // Second sync: update issue title and mark completed
        $integration->refresh();
        (new SyncLinearIssuesForUser($integration))->handle();

        $task = Task::where('linear_id', 'i-1')->first();
        $this->assertNotNull($task);
        $this->assertEquals('Issue One Updated', $task->name);
        $this->assertNotNull($task->done_at);
    }

    public function test_webhook_creates_task_via_job(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'lu-webhook',
        ]);

        // Simulate processing a webhook payload directly through the job
        $job = new ProcessLinearWebhook([
            'action' => 'create',
            'type' => 'Issue',
            'data' => [
                'id' => 'webhook-issue-1',
                'title' => 'Issue from webhook',
                'assignee' => ['id' => 'lu-webhook'],
                'state' => ['type' => 'started'],
                'project' => ['id' => 'proj-w1', 'name' => 'Webhook Project'],
                'estimate' => 2.0,
            ],
        ]);
        $job->handle();

        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'webhook-issue-1',
            'name' => 'Issue from webhook',
            'organization_id' => $organization->getKey(),
        ]);
        $this->assertDatabaseHas('projects', [
            'linear_project_id' => 'proj-w1',
            'name' => 'Webhook Project',
            'organization_id' => $organization->getKey(),
        ]);

        $task = Task::where('linear_id', 'webhook-issue-1')->first();
        $this->assertNotNull($task);
        $this->assertEquals(7200, $task->estimated_time); // 2.0 * 3600
    }

    public function test_webhook_update_modifies_existing_task(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'lu-update',
        ]);

        // First: create via webhook
        (new ProcessLinearWebhook([
            'action' => 'create',
            'type' => 'Issue',
            'data' => [
                'id' => 'update-issue',
                'title' => 'Original Title',
                'assignee' => ['id' => 'lu-update'],
                'state' => ['type' => 'started'],
                'project' => null,
                'estimate' => null,
            ],
        ]))->handle();

        $this->assertDatabaseHas('tasks', ['linear_id' => 'update-issue', 'name' => 'Original Title']);

        // Second: update via webhook
        (new ProcessLinearWebhook([
            'action' => 'update',
            'type' => 'Issue',
            'data' => [
                'id' => 'update-issue',
                'title' => 'Updated Title',
                'assignee' => ['id' => 'lu-update'],
                'state' => ['type' => 'completed'],
                'project' => null,
                'estimate' => 5.0,
            ],
        ]))->handle();

        $task = Task::where('linear_id', 'update-issue')->first();
        $this->assertNotNull($task);
        $this->assertEquals('Updated Title', $task->name);
        $this->assertNotNull($task->done_at);
        $this->assertEquals(18000, $task->estimated_time); // 5.0 * 3600
    }
}
