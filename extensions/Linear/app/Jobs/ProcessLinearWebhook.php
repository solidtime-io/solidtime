<?php

declare(strict_types=1);

namespace Extensions\Linear\Jobs;

use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLinearWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(LinearSyncService $syncService): void
    {
        $action = $this->payload['action'] ?? '';
        $issueData = $this->payload['data'] ?? [];
        $assigneeId = $issueData['assignee']['id'] ?? null;

        if ($assigneeId === null) {
            return;
        }

        // Find integrations matching this Linear user
        $integrations = LinearIntegration::where('linear_user_id', $assigneeId)->get();

        foreach ($integrations as $integration) {
            if ($action === 'create' || $action === 'update') {
                $syncService->upsertTask($integration->organization, $issueData);
            }
            // 'remove' action: intentionally do nothing — preserve tracked time
        }
    }
}
