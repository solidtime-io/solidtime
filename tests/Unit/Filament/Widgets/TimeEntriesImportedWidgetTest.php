<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\TimeEntriesImported;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(TimeEntriesImported::class)]
class TimeEntriesImportedWidgetTest extends ChartWidgetTestCase
{
    public function test_widget_renders_with_filters(): void
    {
        // Act
        $response = Livewire::test(TimeEntriesImported::class);

        // Assert
        $response->assertSuccessful();
        $response->assertSee('Time Entries Imported');
        $response->assertSee('Last week');
        $response->assertSee('Last month');
        $response->assertSee('Last year');
        $response->assertSet('filter', 'week');
    }

    public function test_counts_only_imported_time_entries_created_in_the_last_week_by_default(): void
    {
        // Arrange
        TimeEntry::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_imported' => true],
            ['created_at' => now()->subDays(2), 'is_imported' => true],
            ['created_at' => now()->subDays(2), 'is_imported' => false],
            ['created_at' => now()->subDays(10), 'is_imported' => true],
        ]);

        // Act
        $response = Livewire::test(TimeEntriesImported::class);

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame('Time Entries Imported', $data['datasets'][0]['label']);
        $this->assertSame(2, $this->sumOfChartData($data));
        $this->assertCount(8, $data['labels']);
        $this->assertSame(now()->subWeek()->format('Y-m-d'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m-d'), $data['labels']->last());
    }

    public function test_month_filter_counts_time_entries_created_in_the_last_month_per_day(): void
    {
        // Arrange
        TimeEntry::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_imported' => true],
            ['created_at' => now()->subDays(10), 'is_imported' => true],
            ['created_at' => now()->subDays(10), 'is_imported' => false],
            ['created_at' => now()->subMonths(2), 'is_imported' => true],
        ]);

        // Act
        $response = Livewire::test(TimeEntriesImported::class)
            ->set('filter', 'month');

        // Assert
        $response->assertSuccessful();
        $response->assertDispatched('updateChartData');
        $data = $this->getChartData($response);
        $this->assertSame(2, $this->sumOfChartData($data));
        $this->assertSame(now()->subMonth()->format('Y-m-d'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m-d'), $data['labels']->last());
    }

    public function test_year_filter_counts_time_entries_created_in_the_last_year_per_month(): void
    {
        // Arrange
        TimeEntry::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_imported' => true],
            ['created_at' => now()->subMonths(3), 'is_imported' => true],
            ['created_at' => now()->subMonths(3), 'is_imported' => false],
            ['created_at' => now()->subYears(2), 'is_imported' => true],
        ]);

        // Act
        $response = Livewire::test(TimeEntriesImported::class)
            ->set('filter', 'year');

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame(2, $this->sumOfChartData($data));
        $this->assertCount(13, $data['labels']);
        $this->assertSame(now()->subYear()->format('Y-m'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m'), $data['labels']->last());
    }

    public function test_year_filter_contains_every_month_if_today_is_the_end_of_a_month(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-10-31 12:00:00'));

        // Act
        $response = Livewire::test(TimeEntriesImported::class)
            ->set('filter', 'year');

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame([
            '2025-10', '2025-11', '2025-12', '2026-01', '2026-02', '2026-03', '2026-04',
            '2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10',
        ], $data['labels']->values()->all());
    }

    public function test_unknown_filter_falls_back_to_last_week(): void
    {
        // Arrange
        TimeEntry::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_imported' => true],
            ['created_at' => now()->subDays(10), 'is_imported' => true],
        ]);

        // Act
        $response = Livewire::test(TimeEntriesImported::class)
            ->set('filter', 'unknown');

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame(1, $this->sumOfChartData($data));
        $this->assertCount(8, $data['labels']);
    }
}
