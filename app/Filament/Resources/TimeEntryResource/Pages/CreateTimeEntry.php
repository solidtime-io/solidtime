<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['member_id'])) {
            /** @var Member|null $member */
            $member = Member::query()->find($data['member_id']);
            if ($member !== null) {
                $data['user_id'] = $member->user_id;
                $data['organization_id'] = $member->organization_id;
            }
        }

        return $data;
    }
}
