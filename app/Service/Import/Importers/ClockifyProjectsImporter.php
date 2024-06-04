<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
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
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $this->colorService->getRandomColor(),
                        'is_billable' => $record['Billability'] === 'Yes',
                        'billable_rate' => $billableRateKey !== null && $record[$billableRateKey] !== '' ? (int) (((float) $record[$billableRateKey]) * 100) : null,
                    ]);
                }

                if ($record['Task'] !== '') {
                    $tasks = explode(', ', $record['Task']);
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
            'Task',
        ];
        foreach ($requiredFields as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
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
