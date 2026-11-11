<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\Member;
use App\Models\TimeEntry;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    private ?string $originalProjectId = null;

    private ?string $originalTaskId = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->after(function (TimeEntry $record): void {
                    TimeEntryResource::recalculateSpentTime([$record->project_id], [$record->task_id]);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['member_id'])) {
            /** @var Member|null $member */
            $member = Member::query()->find($data['member_id']);
            if ($member !== null) {
                $data['user_id'] = $member->user_id;
                $data['organization_id'] = $member->organization_id;
            }
        }
        $data['client_id'] = TimeEntryResource::getClientIdForProject($data['project_id'] ?? null);

        return $data;
    }

    protected function beforeSave(): void
    {
        /** @var TimeEntry $timeEntry */
        $timeEntry = $this->getRecord();
        $this->originalProjectId = $timeEntry->project_id;
        $this->originalTaskId = $timeEntry->task_id;
    }

    protected function afterSave(): void
    {
        /** @var TimeEntry $timeEntry */
        $timeEntry = $this->getRecord();
        TimeEntryResource::recalculateSpentTime(
            [$this->originalProjectId, $timeEntry->project_id],
            [$this->originalTaskId, $timeEntry->task_id]
        );
    }
}
