<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Check if the application is up and running
     * This check does not check the database or cache connectivity
     */
    public function up(): JsonResponse
    {
        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Debug information for the application
     * This check checks the database and cache connectivity
     */
    public function debug(Request $request): JsonResponse
    {
        // Check database connectivity
        User::query()->count();

        // Check cache connectivity
        Cache::put('health-check', Carbon::now()->timestamp);

        // Check ip address correct behind load balancer
        $ipAddress = $request->ip();
        $hostname = $request->getHost();
        $secure = $request->secure();
        $isTrustedProxy = $request->isFromTrustedProxy();

        $dbTimezone = DB::select('show timezone;');

        $response = [
            'ip_address' => $ipAddress,
            'url' => $request->url(),
            'path' => $request->path(),
            'hostname' => $hostname,
            'timestamp' => Carbon::now()->timestamp,
            'date_time_utc' => Carbon::now('UTC')->toDateTimeString(),
            'date_time_app' => Carbon::now()->toDateTimeString(),
            'timezone' => $dbTimezone[0]->TimeZone,
            'secure' => $secure,
            'is_trusted_proxy' => $isTrustedProxy,
        ];

        if (app()->hasDebugModeEnabled()) {
            $response['app_debug'] = true;
            $response['app_url'] = config('app.url');
            $response['app_env'] = app()->environment();
            $response['app_timezone'] = config('app.timezone');
            $response['app_force_https'] = config('app.force_https');
            $response['trusted_proxies'] = config('trustedproxy.proxies');
            $headers = $request->headers->all();
            if (isset($headers['cookie'])) {
                $headers['cookie'] = '***';
            }
            $response['headers'] = $headers;
        }

        return response()
            ->json($response);
    }
}
