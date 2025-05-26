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

class GenericTimeEntriesImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    private const array REQUIRED_FIELDS = [
        'description',
        'billable',
        'client',
        'project',
        'tags',
        'start',
        'end',
        'task',
        'user_name',
        'user_email',
    ];

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
        $tagsParsed = explode(',', $tags);
        $tagIds = [];
        foreach ($tagsParsed as $tagParsed) {
            $tagId = $this->tagImportHelper->getKey([
                'name' => Str::trim($tagParsed),
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
                    'email' => $record['user_email'],
                ], [
                    'name' => $record['user_name'],
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
                if ($record['client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                $project = null;
                $projectMember = null;
                if ($record['project'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['project'],
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
                if ($record['task'] !== '') {
                    $taskId = $this->taskImportHelper->getKey([
                        'name' => $record['task'],
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
                $timeEntry->description = $record['description'];
                if (! in_array($record['billable'], ['true', 'false'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['billable'] === 'true';
                $timeEntry->tags = $this->getTags($record['tags']);
                $timeEntry->is_imported = true;
                try {
                    $start = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $record['start'], 'UTC');
                } catch (InvalidFormatException) {
                    throw new ImportException('Value of start ("'.$record['start'].'") is invalid');
                }
                if ($start === null) {
                    throw new ImportException('Value of start ("'.$record['start'].'") is invalid');
                }
                $timeEntry->start = $start->utc();

                try {
                    $end = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $record['end'], 'UTC');
                } catch (InvalidFormatException) {
                    throw new ImportException('Value of end ("'.$record['end'].'") is invalid');
                }
                if ($end === null) {
                    throw new ImportException('Value of end ("'.$record['end'].'") is invalid');
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
        foreach (self::REQUIRED_FIELDS as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
    }

    #[\Override]
    public function getName(): string
    {
        return __('importer.generic_time_entries.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.generic_time_entries.description');
    }
}
