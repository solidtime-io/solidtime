<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class ClockifyProjectsImporter extends DefaultImporter
{
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
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                if ($record['Name'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['Name'],
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $this->colorService->getRandomColor(),
                    ]);
                }

                if ($record['Tasks'] !== '') {
                    $tasks = explode(', ', $record['Tasks']);
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
            'Name',
            'Client',
            'Status',
            'Visibility',
            'Billability',
            'Tasks',
        ];
        foreach ($requiredFields as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
    }
}
