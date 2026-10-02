<?php

declare(strict_types=1);

namespace App\Filament\Resources\GoalResource\Pages;

use App\Filament\Resources\GoalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGoal extends ViewRecord
{
    protected static string $resource = GoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make('edit')
                ->icon('heroicon-s-pencil'),
        ];
    }
}
