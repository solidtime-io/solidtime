<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\UserRegistrations;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(UserRegistrations::class)]
class UserRegistrationsWidgetTest extends ChartWidgetTestCase
{
    public function test_widget_renders_with_filters(): void
    {
        // Act
        $response = Livewire::test(UserRegistrations::class);

        // Assert
        $response->assertSuccessful();
        $response->assertSee('User Registrations');
        $response->assertSee('Last week');
        $response->assertSee('Last month');
        $response->assertSee('Last year');
        $response->assertSet('filter', 'week');
    }

    public function test_counts_only_non_placeholder_users_registered_in_the_last_week_by_default(): void
    {
        // Arrange
        // The super admin from setUp was created just now and is therefore counted as well.
        User::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_placeholder' => false],
            ['created_at' => now()->subDays(2), 'is_placeholder' => false],
            ['created_at' => now()->subDays(2), 'is_placeholder' => true],
            ['created_at' => now()->subDays(10), 'is_placeholder' => false],
        ]);

        // Act
        $response = Livewire::test(UserRegistrations::class);

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame('User Registrations', $data['datasets'][0]['label']);
        $this->assertSame(3, $this->sumOfChartData($data));
        $this->assertCount(8, $data['labels']);
        $this->assertSame(now()->subWeek()->format('Y-m-d'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m-d'), $data['labels']->last());
    }

    public function test_month_filter_counts_users_registered_in_the_last_month_per_day(): void
    {
        // Arrange
        User::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_placeholder' => false],
            ['created_at' => now()->subDays(10), 'is_placeholder' => false],
            ['created_at' => now()->subDays(10), 'is_placeholder' => true],
            ['created_at' => now()->subMonths(2), 'is_placeholder' => false],
        ]);

        // Act
        $response = Livewire::test(UserRegistrations::class)
            ->set('filter', 'month');

        // Assert
        $response->assertSuccessful();
        $response->assertDispatched('updateChartData');
        $data = $this->getChartData($response);
        $this->assertSame(3, $this->sumOfChartData($data));
        $this->assertSame(now()->subMonth()->format('Y-m-d'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m-d'), $data['labels']->last());
    }

    public function test_year_filter_counts_users_registered_in_the_last_year_per_month(): void
    {
        // Arrange
        User::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_placeholder' => false],
            ['created_at' => now()->subMonths(3), 'is_placeholder' => false],
            ['created_at' => now()->subMonths(3), 'is_placeholder' => true],
            ['created_at' => now()->subYears(2), 'is_placeholder' => false],
        ]);

        // Act
        $response = Livewire::test(UserRegistrations::class)
            ->set('filter', 'year');

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame(3, $this->sumOfChartData($data));
        $this->assertCount(13, $data['labels']);
        $this->assertSame(now()->subYear()->format('Y-m'), $data['labels']->first());
        $this->assertSame(now()->format('Y-m'), $data['labels']->last());
    }

    public function test_unknown_filter_falls_back_to_last_week(): void
    {
        // Arrange
        User::factory()->createMany([
            ['created_at' => now()->subDay(), 'is_placeholder' => false],
            ['created_at' => now()->subDays(10), 'is_placeholder' => false],
        ]);

        // Act
        $response = Livewire::test(UserRegistrations::class)
            ->set('filter', 'unknown');

        // Assert
        $response->assertSuccessful();
        $data = $this->getChartData($response);
        $this->assertSame(2, $this->sumOfChartData($data));
        $this->assertCount(8, $data['labels']);
    }
}
