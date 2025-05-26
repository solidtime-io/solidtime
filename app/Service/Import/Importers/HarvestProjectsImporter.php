<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
use Illuminate\Support\Str;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class HarvestProjectsImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    private const array REQUIRED_FIELDS = [
        'Client',
        'Project',
        'Budget',
        'Billable Hours',
    ];

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
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                if ($record['Project'] !== '') {
                    if (! isset($record['Budget']) || ! is_string($record['Budget'])) {
                        throw new ImportException('The value for "Budget" is invalid');
                    }
                    $estimatedTimeField = Str::replace(',', '.', $record['Budget']);
                    $estimatedTime = $estimatedTimeField !== '' && is_numeric($estimatedTimeField) ? (int) (((float) $estimatedTimeField) * 60 * 60) : null;
                    if ($estimatedTime === 0) {
                        $estimatedTime = null;
                    }
                    if (! isset($record['Billable Hours']) || ! is_string($record['Billable Hours'])) {
                        throw new ImportException('The value for "Billable Hours" is invalid');
                    }
                    $billableHoursField = Str::replace(',', '.', $record['Billable Hours']);
                    $billableHours = $billableHoursField !== '' && is_numeric($billableHoursField) ? (int) ((float) $billableHoursField) : null;
                    $this->projectImportHelper->getKey([
                        'name' => $record['Project'],
                        'client_id' => $clientId,
                        'organization_id' => $this->organization->id,
                    ], [
                        'color' => $this->colorService->getRandomColor(),
                        'estimated_time' => $estimatedTime,
                        'is_billable' => $billableHours > 0,
                    ]);
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
        foreach (self::REQUIRED_FIELDS as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
    }

    #[\Override]
    public function getName(): string
    {
        return __('importer.harvest_projects.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.harvest_projects.description');
    }
}
