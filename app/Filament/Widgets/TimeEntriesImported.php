<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TimeEntriesImported extends ChartWidget
{
    protected static ?string $heading = 'Time Entries Imported';

    public ?string $filter = 'week';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $filter = $this->filter;
        if ($filter === 'week') {
            $start = now()->subWeek();
        } elseif ($filter === 'month') {
            $start = now()->subMonth();
        } elseif ($filter === 'year') {
            $start = now()->subYear();
        } else {
            $start = now()->subWeek();
        }
        $trend = Trend::query(
            TimeEntry::query()->where('is_imported', '=', true)
        )
            ->between(
                start: $start,
                end: now(),
            )
            ->perDay();

        if ($filter === 'week') {
            $trend->perDay();
        } elseif ($filter === 'month') {
            $trend->perDay();
        } elseif ($filter === 'year') {
            $trend->perMonth();
        } else {
            $trend->perDay();
        }

        $data = $trend->count();

        return [
            'datasets' => [
                [
                    'label' => self::$heading,
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last week',
            'month' => 'Last month',
            'year' => 'Last year',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
