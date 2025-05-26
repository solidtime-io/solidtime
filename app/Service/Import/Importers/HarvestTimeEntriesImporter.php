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
use Override;

class HarvestTimeEntriesImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    private const array REQUIRED_FIELDS = [
        'Date',
        'Hours',
        'Client',
        'Project',
        'Task',
        'Billable?',
        'First Name',
        'Last Name',
        'Notes',
    ];

    /**
     * @throws ImportException
     */
    #[Override]
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
                $firstname = $record['First Name'];
                $lastname = $record['Last Name'];
                $userId = $this->userImportHelper->getKey([
                    'email' => Str::slug($firstname).'.'.Str::slug($lastname).'@solidtime-import.test',
                ], [
                    'name' => $firstname.' '.$lastname,
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
                        'color' => $this->colorService->getRandomColor(),
                        'is_billable' => true,
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
                if (strlen($record['Notes']) > 500) {
                    throw new ImportException('Time entry note is too long');
                }
                $timeEntry->description = $record['Notes'];
                if (! in_array($record['Billable?'], ['Yes', 'No'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['Billable?'] === 'Yes';
                $timeEntry->tags = [];
                $timeEntry->is_imported = true;

                // Start & End
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $record['Date'], $timezone);
                } catch (InvalidFormatException) {
                    throw new ImportException('Date ("'.$record['Date'].'") is invalid');
                }
                if ($date === null) {
                    throw new ImportException('Date ("'.$record['Date'].'") is invalid');
                }
                if (! isset($record['Hours']) || ! is_string($record['Hours'])) {
                    throw new ImportException('Hours ("'.($record['Hours'] ?? '<null>').'") is invalid');
                }
                $hoursField = Str::replace(',', '.', $record['Hours']);
                if (! is_numeric($hoursField)) {
                    throw new ImportException('Hours ("'.$record['Hours'].'") is invalid');
                }
                $hours = (float) $hoursField;
                $timeEntry->start = $date->copy()->startOfDay()->utc();
                $timeEntry->end = $date->copy()->startOfDay()->addHours($hours)->utc();
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

    #[Override]
    public function getName(): string
    {
        return __('importer.harvest_time_entries.name');
    }

    #[Override]
    public function getDescription(): string
    {
        return __('importer.harvest_time_entries.description');
    }
}
