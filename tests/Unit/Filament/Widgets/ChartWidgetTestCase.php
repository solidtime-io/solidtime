<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Widgets;

use App\Models\User;
use Closure;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Livewire\Features\SupportTesting\Testable;
use Tests\Unit\Filament\FilamentTestCase;

abstract class ChartWidgetTestCase extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);
    }

    /**
     * Calls the protected getData() method of the chart widget of the given Livewire test.
     *
     * @param  Testable<ChartWidget>  $testable
     * @return array{datasets: array<int, array{label: string, data: Collection<int, int>}>, labels: Collection<int, string>}
     */
    protected function getChartData(Testable $testable): array
    {
        /** @var ChartWidget $widget */
        $widget = $testable->instance();

        /** @var array{datasets: array<int, array{label: string, data: Collection<int, int>}>, labels: Collection<int, string>} $data */
        $data = Closure::bind(fn (): array => $this->getData(), $widget, $widget)();

        return $data;
    }

    /**
     * @param  array{datasets: array<int, array{label: string, data: Collection<int, int>}>, labels: Collection<int, string>}  $data
     */
    protected function sumOfChartData(array $data): int
    {
        return (int) $data['datasets'][0]['data']->sum();
    }
}
