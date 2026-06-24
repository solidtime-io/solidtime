<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class ClockifyProjectsImporter extends DefaultImporter
{
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
            $billableRateKey = $this->getBillableRateKey($header);
            $tasksKey = $this->getTasksKey($header);
            $records = $reader->getRecords();
            foreach ($records as $record) {
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
                        'client_id' => $clientId,
                        'organization_id' => $this->organization->id,
                    ], [
                        'color' => $this->colorService->getRandomColor(),
                        'is_billable' => $record['Billability'] === 'Yes',
                        'billable_rate' => $billableRateKey !== null && $record[$billableRateKey] !== '' ? (int) (((float) $record[$billableRateKey]) * 100) : null,
                        'estimated_time' => $record['Estimated (h)'] !== '' && is_numeric($record['Estimated (h)']) ? (int) ($record['Estimated (h)'] * 3600) : null,
                        'archived_at' => $record['Status'] === 'Archived' ? Carbon::now() : null,
                    ]);
                }

                if ($record[$tasksKey] !== '') {
                    $tasks = explode(', ', $record[$tasksKey]);
                    foreach ($tasks as $task) {
                        $this->taskImportHelper->getKey([
                            'name' => $task,
                            'project_id' => $projectId,
                            'organization_id' => $this->organization->id,
                        ]);
                    }
                }
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
            'Status',
            'Visibility',
            'Billability',
        ];
        foreach ($requiredFields as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
        // Clockify renamed the "Task" column to "Tasks" in newer exports; accept either.
        if (! in_array('Task', $header, true) && ! in_array('Tasks', $header, true)) {
            throw new ImportException('Invalid CSV header, missing field: Tasks');
        }
    }

    /**
     * Clockify renamed the "Task" column to "Tasks" in newer exports.
     *
     * @param  array<string>  $header
     */
    private function getTasksKey(array $header): string
    {
        return in_array('Tasks', $header, true) ? 'Tasks' : 'Task';
    }

    /**
     * @param  array<string>  $header
     */
    private function getBillableRateKey(array $header): ?string
    {
        $billableRateKey = null;
        foreach ($header as $value) {
            if (Str::startsWith($value, 'Billable Rate (')) {
                $billableRateKey = $value;
                break;
            }
        }

        return $billableRateKey;
    }

    #[\Override]
    public function getName(): string
    {
        return __('importer.clockify_projects.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.clockify_projects.description');
    }
}
