<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Enums\Role;
use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\TimeEntry;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class TogglTimeEntriesImporter extends DefaultImporter
{
    /**
     * @return array<string>
     *
     * @throws ImportException
     */
    private function getTags(string $tags): array
    {
        if (Str::trim($tags) === '') {
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
            $reader->setEnclosure('"');
            $reader->setEscape('');
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
                        'client_id' => $clientId,
                        'organization_id' => $this->organization->id,
                    ], [
                        'is_billable' => false,
                        'color' => $this->colorService->getRandomColor(),
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
                    $this->taskImportHelper->getModelById($taskId);
                }
                $timeEntry = new TimeEntry;
                $timeEntry->disableAuditing();
                $timeEntry->user_id = $userId;
                $timeEntry->member_id = $memberId;
                $timeEntry->task_id = $taskId;
                $timeEntry->project_id = $projectId;
                $timeEntry->client_id = $clientId;
                $timeEntry->organization_id = $this->organization->id;
                $timeEntry->description = $record['Description'];
                if (! in_array($record['Billable'], ['Yes', 'No'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['Billable'] === 'Yes';
                $timeEntry->tags = $this->getTags($record['Tags']);
                $timeEntry->is_imported = true;
                try {
                    $start = Carbon::createFromFormat('Y-m-d H:i:s', $record['Start date'].' '.$record['Start time'], $timezone);
                } catch (InvalidFormatException) {
                    throw new ImportException('Start date ("'.$record['Start date'].'") or time ("'.$record['Start time'].'") are invalid');
                }
                if ($start === null) {
                    throw new ImportException('Start date ("'.$record['Start date'].'") or time ("'.$record['Start time'].'") are invalid');
                }
                $timeEntry->start = $start->utc();

                try {
                    $end = Carbon::createFromFormat('Y-m-d H:i:s', $record['End date'].' '.$record['End time'], $timezone);
                } catch (InvalidFormatException) {
                    throw new ImportException('End date ("'.$record['End date'].'") or time ("'.$record['End time'].'") are invalid');
                }
                if ($end === null) {
                    throw new ImportException('End date ("'.$record['End date'].'") or time ("'.$record['End time'].'") are invalid');
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
            foreach ($this->projectImportHelper->getCachedModels() as $usedProject) {
                RecalculateSpentTimeForProject::dispatch($usedProject);
            }
            foreach ($this->taskImportHelper->getCachedModels() as $usedTask) {
                RecalculateSpentTimeForTask::dispatch($usedTask);
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
            'User',
            'Email',
            'Client',
            'Project',
            'Task',
            'Description',
            'Billable',
            'Start date',
            'Start time',
            'End date',
            'End time',
            'Tags',
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
        return __('importer.toggl_time_entries.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.toggl_time_entries.description');
    }
}
