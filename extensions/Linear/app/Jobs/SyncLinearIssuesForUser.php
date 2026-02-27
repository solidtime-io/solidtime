<?php

declare(strict_types=1);

namespace Extensions\Linear\Jobs;

use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearGraphQLClient;
use Extensions\Linear\Services\LinearSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncLinearIssuesForUser implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly LinearIntegration $integration,
    ) {}

    public function handle(LinearSyncService $syncService = new LinearSyncService): void
    {
        $client = new LinearGraphQLClient($this->integration->access_token);

        $updatedFilter = '';
        if ($this->integration->last_synced_at !== null) {
            $updatedFilter = ', updatedAt: { gt: "'.$this->integration->last_synced_at->toIso8601String().'" }';
        }

        $query = <<<GRAPHQL
            query (\$assigneeId: String!, \$after: String) {
                issues(
                    filter: { assignee: { id: { eq: \$assigneeId } }$updatedFilter },
                    orderBy: updatedAt,
                    first: 50,
                    after: \$after
                ) {
                    nodes {
                        id
                        title
                        state { type }
                        project { id name }
                        estimate
                        updatedAt
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'assigneeId' => $this->integration->linear_user_id,
        ];

        $hasNextPage = true;
        $cursor = null;

        while ($hasNextPage) {
            if ($cursor !== null) {
                $variables['after'] = $cursor;
            }

            try {
                $result = $client->query($query, $variables);
            } catch (RuntimeException $e) {
                Log::error('Linear sync failed: '.$e->getMessage(), [
                    'integration_id' => $this->integration->getKey(),
                ]);

                return;
            }

            $issues = $result['issues']['nodes'] ?? [];
            foreach ($issues as $issue) {
                $syncService->upsertTask($this->integration->organization, $issue);
            }

            $pageInfo = $result['issues']['pageInfo'];
            $hasNextPage = $pageInfo['hasNextPage'];
            $cursor = $pageInfo['endCursor'];
        }

        $this->integration->update(['last_synced_at' => now()]);
    }
}
