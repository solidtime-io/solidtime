<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Audit;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Log;

class ApiService
{
    private const string API_URL = 'https://app.solidtime.io/api';

    public function checkForUpdate(): ?string
    {
        try {
            $response = Http::asJson()
                ->timeout(3)
                ->connectTimeout(2)
                ->post(self::API_URL.'/check-for-update', [
                    'version' => config('app.version'),
                    'build' => config('app.build'),
                    'url' => config('app.url'),
                ]);

            if ($response->status() === 200 && isset($response->json()['version']) && is_string($response->json()['version'])) {
                return $response->json()['version'];
            } else {
                Log::warning('Failed to check for update', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to check for update', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function telemetry(): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(3)
                ->connectTimeout(2)
                ->post(self::API_URL.'/telemetry', [
                    'version' => config('app.version'),
                    'build' => config('app.build'),
                    'url' => config('app.url'),
                    // telemetry data
                    'user_count' => User::count(),
                    'organization_count' => Organization::count(),
                    'audit_count' => Audit::count(),
                    'project_count' => Project::count(),
                    'project_member_count' => ProjectMember::count(),
                    'client_count' => Client::count(),
                    'task_count' => Task::count(),
                    'time_entry_count' => TimeEntry::count(),
                ]);

            if ($response->status() === 200) {
                return true;
            } else {
                Log::warning('Failed send telemetry data', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }
        } catch (Exception $e) {
            Log::warning('Failed send telemetry data', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
