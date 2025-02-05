<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationInvitationResource\Pages;

use App\Filament\Resources\OrganizationInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationInvitation extends EditRecord
{
    protected static string $resource = OrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash'),
        ];
    }
}
