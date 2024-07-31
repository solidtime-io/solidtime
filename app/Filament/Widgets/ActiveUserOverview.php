<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ActiveUserOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $heading = 'A Registrations';

    protected function getCards(): array
    {
        $usersCount = User::query()->where('is_placeholder', '=', false)->count();
        $placeholderUserCount = User::query()->where('is_placeholder', '=', true)->count();
        $activeInLastWeek = User::query()
            ->where('is_placeholder', '=', false)
            ->whereHas('timeEntries', function (Builder $query): void {
                /** @var Builder<TimeEntry> $query */
                $query->where('created_at', '>=', now()->subWeek())
                    ->orWhere('updated_at', '>=', now()->subWeek());
            })
            ->count();

        return [
            Stat::make('Total', $usersCount)
                ->color('primary')
                ->description('Total real users'),

            Stat::make('Placeholder', $placeholderUserCount)
                ->color('danger')
                ->description('Placeholder users'),

            Stat::make('Active', $activeInLastWeek)
                ->color('success')
                ->description('Active users in the last seven days'),
        ];
    }
}
