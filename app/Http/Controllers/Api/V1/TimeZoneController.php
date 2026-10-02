<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Service\TimezoneService;
use Illuminate\Http\JsonResponse;

class TimeZoneController extends Controller
{
    /**
     * Get all timezones
     *
     * @response array{key: string}[]
     *
     * @operationId getTimezones
     */
    public function index(): JsonResponse
    {
        $timezones = app(TimezoneService::class)->getTimezones();

        $response = [];

        foreach ($timezones as $timezone) {
            $response[] = (object) [
                'key' => $timezone,
            ];
        }

        return response()->json($response);
    }
}
