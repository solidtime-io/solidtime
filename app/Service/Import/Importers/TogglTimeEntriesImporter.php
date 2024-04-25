<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\TimeEntry;
use Exception;
use Illuminate\Support\Carbon;
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
    public function importData(string $data): void
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
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                if ($record['Project'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['Project'],
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $this->colorService->getRandomColor(),
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
                $timeEntry = new TimeEntry();
                $timeEntry->user_id = $userId;
                $timeEntry->task_id = $taskId;
                $timeEntry->project_id = $projectId;
                $timeEntry->organization_id = $this->organization->id;
                $timeEntry->description = $record['Description'];
                if (! in_array($record['Billable'], ['Yes', 'No'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['Billable'] === 'Yes';
                $timeEntry->tags = $this->getTags($record['Tags']);
                $start = Carbon::createFromFormat('Y-m-d H:i:s', $record['Start date'].' '.$record['Start time'], 'UTC');
                if ($start === null) {
                    throw new ImportException('Start date ("'.$record['Start date'].'") or time ("'.$record['Start time'].'") are invalid');
                }
                $timeEntry->start = $start;
                $end = Carbon::createFromFormat('Y-m-d H:i:s', $record['End date'].' '.$record['End time'], 'UTC');
                if ($end === null) {
                    throw new ImportException('End date ("'.$record['End date'].'") or time ("'.$record['End time'].'") are invalid');
                }
                $timeEntry->end = $end;
                $timeEntry->setComputedAttributeValue('billable_rate');
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
