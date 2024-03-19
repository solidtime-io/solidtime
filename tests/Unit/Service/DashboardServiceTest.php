<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_tracked_hours_returns_correct_values(): void
    {
        // Arrange
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'UTC'));
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
        ]);
        $timeEntry = TimeEntry::factory()->forUser($user)->create([
            'start' => Carbon::create(2023, 12, 31, 0, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 31, 0, 0, 40, 'UTC'),
        ]);

        // Act
        $service = new DashboardService();
        $result = $service->getDailyTrackedHours($user, 5);

        // Assert
        $this->assertSame([
            ['2024-01-01', 0],
            ['2023-12-31', 40],
            ['2023-12-30', 0],
            ['2023-12-29', 0],
            ['2023-12-28', 0],
        ], $result);
    }
}
