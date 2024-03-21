<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Weekday;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;

    public function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    public function test_daily_tracked_hours_returns_correct_values(): void
    {
        // Arrange
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
        ]);
        $timeEntry1 = TimeEntry::factory()->forUser($user)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        $timeEntry2 = TimeEntry::factory()->forUser($user)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->getDailyTrackedHours($user, 5);

        // Assert
        $this->assertSame([
            [
                'date' => '2023-12-28',
                'duration' => 0,
            ],
            [
                'date' => '2023-12-29',
                'duration' => 0,
            ],
            [
                'date' => '2023-12-30',
                'duration' => 40,
            ],
            [
                'date' => '2023-12-31',
                'duration' => 40,
            ],
            [
                'date' => '2024-01-01',
                'duration' => 0,
            ],
        ], $result);
    }

    public function test_weekly_history_returns_correct_values(): void
    {
        // Arrange
        // Note: Is a Monday
        $this->travelTo(Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna'));
        $user = User::factory()->create([
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday,
        ]);
        // Note: This is a Sunday
        $timeEntry1 = TimeEntry::factory()->forUser($user)->create([
            // Note: The start time shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 23, 0, 0, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 40, 'UTC'),
        ]);
        // Note: This is a Saturday
        $timeEntry2 = TimeEntry::factory()->forUser($user)->create([
            // Note: The start time NOT shifts in timezone Europe/Vienna to the next day
            'start' => Carbon::create(2023, 12, 30, 22, 59, 59, 'UTC'),
            'end' => Carbon::create(2023, 12, 30, 23, 0, 39, 'UTC'),
        ]);

        // Act
        $result = $this->dashboardService->getWeeklyHistory($user);

        // Assert
        $this->assertSame([
            [
                'date' => '2023-12-31',
                'duration' => 40,
            ],
            [
                'date' => '2024-01-01',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-02',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-03',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-04',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-05',
                'duration' => 0,
            ],
            [
                'date' => '2024-01-06',
                'duration' => 0,
            ],
        ], $result);
    }
}
