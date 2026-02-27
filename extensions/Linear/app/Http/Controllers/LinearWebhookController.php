<?php

declare(strict_types=1);

namespace Extensions\Linear\Http\Controllers;

use Extensions\Linear\Jobs\ProcessLinearWebhook;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinearWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('Linear-Signature');
        $payload = $request->getContent();

        if ($signature === null) {
            return new JsonResponse(['error' => 'Missing signature'], 401);
        }

        // Find integration by checking signature against all webhook secrets
        $verified = false;
        $integrations = LinearIntegration::whereNotNull('webhook_secret')->get();

        foreach ($integrations as $integration) {
            $expected = hash_hmac('sha256', $payload, $integration->webhook_secret);
            if (hash_equals($expected, $signature)) {
                $verified = true;
                break;
            }
        }

        if (! $verified) {
            Log::warning('Linear webhook signature verification failed');

            return new JsonResponse(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();

        // Only process Issue events
        if (($data['type'] ?? '') !== 'Issue') {
            return new JsonResponse(['message' => 'Ignored']);
        }

        ProcessLinearWebhook::dispatch($data);

        return new JsonResponse(['message' => 'Accepted']);
    }
}
