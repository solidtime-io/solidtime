<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Enums\Role;
use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['personal_team'] = false;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Organization $organization */
        $organization = $this->record;

        $user = $organization->owner;

        $organization->users()->attach(
            $user, [
                'role' => Role::Owner->value,
            ]
        );
    }
}
