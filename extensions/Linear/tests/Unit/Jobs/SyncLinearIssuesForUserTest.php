<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Jobs;

use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDatabase;

class SyncLinearIssuesForUserTest extends TestCaseWithDatabase
{
    public function test_sync_fetches_assigned_issues_and_creates_tasks(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-abc',
            'last_synced_at' => now()->subHour(),
        ]);

        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => [
                    'issues' => [
                        'nodes' => [
                            [
                                'id' => 'issue-1',
                                'title' => 'First issue',
                                'state' => ['type' => 'started'],
                                'project' => null,
                                'estimate' => null,
                                'updatedAt' => now()->toIso8601String(),
                            ],
                            [
                                'id' => 'issue-2',
                                'title' => 'Second issue',
                                'state' => ['type' => 'completed'],
                                'project' => ['id' => 'proj-1', 'name' => 'Project Alpha'],
                                'estimate' => 3.0,
                                'updatedAt' => now()->toIso8601String(),
                            ],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ], 200),
        ]);

        $job = new SyncLinearIssuesForUser($integration);
        $job->handle();

        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'issue-1',
            'name' => 'First issue',
            'organization_id' => $organization->getKey(),
        ]);
        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'issue-2',
            'name' => 'Second issue',
        ]);

        $integration->refresh();
        $this->assertNotNull($integration->last_synced_at);
    }

    public function test_sync_handles_graphql_errors_gracefully(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'expired-token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-abc',
            'last_synced_at' => null,
        ]);

        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'errors' => [['message' => 'Authentication required']],
            ], 200),
        ]);

        $job = new SyncLinearIssuesForUser($integration);
        $job->handle();

        // Should not throw, should log error and return
        $integration->refresh();
        $this->assertNull($integration->last_synced_at);
    }

    public function test_sync_paginates_through_results(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'valid-token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-abc',
            'last_synced_at' => null,
        ]);

        Http::fake([
            'api.linear.app/graphql' => Http::sequence()
                ->push([
                    'data' => [
                        'issues' => [
                            'nodes' => [
                                ['id' => 'issue-page1', 'title' => 'Page 1 Issue', 'state' => ['type' => 'started'], 'project' => null, 'estimate' => null, 'updatedAt' => now()->toIso8601String()],
                            ],
                            'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor-1'],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'issues' => [
                            'nodes' => [
                                ['id' => 'issue-page2', 'title' => 'Page 2 Issue', 'state' => ['type' => 'started'], 'project' => null, 'estimate' => null, 'updatedAt' => now()->toIso8601String()],
                            ],
                            'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                        ],
                    ],
                ]),
        ]);

        $job = new SyncLinearIssuesForUser($integration);
        $job->handle();

        $this->assertDatabaseHas('tasks', ['linear_id' => 'issue-page1', 'name' => 'Page 1 Issue']);
        $this->assertDatabaseHas('tasks', ['linear_id' => 'issue-page2', 'name' => 'Page 2 Issue']);
    }
}
