<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Enums\Role;
use App\Models\TimeEntry;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Carbon;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class ClockifyTimeEntriesImporter extends DefaultImporter
{
    /**
     * @return array<string>
     *
     * @throws ImportException
     */
    private function getTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }
        $tagsParsed = explode(', ', $tags);
        $tagIds = [];
        foreach ($tagsParsed as $tagParsed) {
            $tagId = $this->tagImportHelper->getKey([
                'name' => $tagParsed,
                'organization_id' => $this->organization->id,
            ]);
            $tagIds[] = $tagId;
        }

        return $tagIds;
    }

    /**
     * @throws ImportException
     */
    #[\Override]
    public function importData(string $data, string $timezone): void
    {
        try {
            $reader = Reader::createFromString($data);
            $reader->setHeaderOffset(0);
            $reader->setDelimiter(',');
            $header = $reader->getHeader();
            $this->validateHeader($header);
            $records = $reader->getRecords();
            foreach ($records as $record) {
                $userId = $this->userImportHelper->getKey([
                    'email' => $record['Email'],
                ], [
                    'name' => $record['User'],
                    'timezone' => 'UTC',
                    'is_placeholder' => true,
                ]);
                $memberId = $this->memberImportHelper->getKey([
                    'user_id' => $userId,
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'role' => Role::Placeholder->value,
                ]);
                $member = $this->memberImportHelper->getModelById($memberId);
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                $project = null;
                $projectMember = null;
                if ($record['Project'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['Project'],
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $this->colorService->getRandomColor(),
                        'is_billable' => false,
                    ]);
                    $project = $this->projectImportHelper->getModelById($projectId);
                    $projectMember = $this->projectMemberImportHelper->getModel([
                        'project_id' => $projectId,
                        'member_id' => $memberId,
                    ]);
                }
                $taskId = null;
                if ($record['Task'] !== '') {
                    $taskId = $this->taskImportHelper->getKey([
                        'name' => $record['Task'],
                        'project_id' => $projectId,
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $timeEntry = new TimeEntry;
                $timeEntry->user_id = $userId;
                $timeEntry->member_id = $memberId;
                $timeEntry->task_id = $taskId;
                $timeEntry->project_id = $projectId;
                $timeEntry->client_id = $clientId;
                $timeEntry->organization_id = $this->organization->id;
                if (strlen($record['Description']) > 500) {
                    throw new ImportException('Time entry description is too long');
                }
                $timeEntry->description = $record['Description'];
                if (! in_array($record['Billable'], ['Yes', 'No'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['Billable'] === 'Yes';
                $timeEntry->tags = $this->getTags($record['Tags']);
                $timeEntry->is_imported = true;

                // Start
                try {
                    if (preg_match('/^[0-9]{1,2}:[0-9]{1,2} (AM|PM)$/', $record['Start Time']) === 1) {
                        $start = Carbon::createFromFormat('m/d/Y h:i A', $record['Start Date'].' '.$record['Start Time'], $timezone);
                    } else {
                        $start = Carbon::createFromFormat('m/d/Y H:i:s A', $record['Start Date'].' '.$record['Start Time'], $timezone);
                    }
                } catch (InvalidFormatException) {
                    throw new ImportException('Start date ("'.$record['Start Date'].'") or time ("'.$record['Start Time'].'") are invalid');
                }
                if ($start === null) {
                    throw new ImportException('Start date ("'.$record['Start Date'].'") or time ("'.$record['Start Time'].'") are invalid');
                }
                $timeEntry->start = $start->utc();

                // End
                try {
                    if (preg_match('/^[0-9]{1,2}:[0-9]{1,2} (AM|PM)$/', $record['End Time']) === 1) {
                        $end = Carbon::createFromFormat('m/d/Y h:i A', $record['End Date'].' '.$record['End Time'], $timezone);
                    } else {
                        $end = Carbon::createFromFormat('m/d/Y H:i:s A', $record['End Date'].' '.$record['End Time'], $timezone);
                    }
                } catch (InvalidFormatException) {
                    throw new ImportException('End date ("'.$record['End Date'].'") or time ("'.$record['End Time'].'") are invalid');
                }
                if ($end === null) {
                    throw new ImportException('End date ("'.$record['End Date'].'") or time ("'.$record['End Time'].'") are invalid');
                }
                $timeEntry->end = $end->utc();
                $timeEntry->billable_rate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
                    $timeEntry,
                    $projectMember,
                    $project,
                    $member,
                    $this->organization
                );
                $timeEntry->save();
                $this->timeEntriesCreated++;
            }
        } catch (ImportException $exception) {
            throw $exception;
        } catch (CsvException $exception) {
            throw new ImportException('Invalid CSV data');
        } catch (Exception $exception) {
            report($exception);
            throw new ImportException('Unknown error');
        }
    }

    /**
     * @param  array<string>  $header
     *
     * @throws ImportException
     */
    private function validateHeader(array $header): void
    {
        $requiredFields = [
            'Project',
            'Client',
            'Description',
            'Task',
            'User',
            'Group',
            'Email',
            'Tags',
            'Billable',
            'Start Date',
            'Start Time',
            'End Date',
            'End Time',
        ];
        foreach ($requiredFields as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
    }

    #[\Override]
    public function getName(): string
    {
        return __('importer.clockify_time_entries.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.clockify_time_entries.description');
    }
}
