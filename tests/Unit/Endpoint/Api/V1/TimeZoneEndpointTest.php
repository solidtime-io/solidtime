<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\TimeZoneController;
use App\Service\TimezoneService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(TimeZoneController::class)]
#[CoversClass(TimezoneService::class)]
class TimeZoneEndpointTest extends TestCase
{
    public function test_index_returns_list_of_available_timezones(): void
    {
        // Arrange
        $timezones = app(TimezoneService::class)->getTimezones();

        // Act
        $response = $this->getJson(route('api.v1.time-zones.index'));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(count($timezones));
        $response->assertJsonStructure([
            [
                'key',
            ],
        ]);

        $responseObj = collect($response->json());
        $this->assertSame([
            'key' => $timezones[0],
        ], $responseObj->first());
        $this->assertSame([
            'key' => 'Europe/Vienna',
        ], $responseObj->firstWhere('key', '=', 'Europe/Vienna'));
        $this->assertSame([
            'key' => 'America/New_York',
        ], $responseObj->firstWhere('key', '=', 'America/New_York'));
    }
}
