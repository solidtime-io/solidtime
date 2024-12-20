<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectMemberResource\Pages;

use App\Filament\Resources\ProjectMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectMembers extends ListRecords
{
    protected static string $resource = ProjectMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-s-plus'),
        ];
    }
}
