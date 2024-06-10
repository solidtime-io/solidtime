<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectMemberResource\Pages;

use App\Filament\Resources\ProjectMemberResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectMembers extends ViewRecord
{
    protected static string $resource = ProjectMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make('edit')
                ->icon('heroicon-s-pencil'),
        ];
    }
}
