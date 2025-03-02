<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationInvitationResource\Pages;

use App\Filament\Resources\OrganizationInvitationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganizationInvitation extends ViewRecord
{
    protected static string $resource = OrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make('edit')
                ->icon('heroicon-s-pencil'),
        ];
    }
}
