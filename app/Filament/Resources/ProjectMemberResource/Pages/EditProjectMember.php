<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectMemberResource\Pages;

use App\Filament\Resources\ProjectMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectMember extends EditRecord
{
    protected static string $resource = ProjectMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-m-trash'),
        ];
    }
}
