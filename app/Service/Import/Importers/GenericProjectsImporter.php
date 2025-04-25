<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Service\ColorService;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Carbon;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use Override;

class GenericProjectsImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    private const array REQUIRED_FIELDS = [
        'name',
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
                $clientId = null;
                if (isset($record['client']) && $record['client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                if ($record['name'] !== '') {
                    $archivedAt = null;
                    if (isset($record['archived_at']) && $record['archived_at'] !== '') {
                        try {
                            $archivedAt = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $record['archived_at'], 'UTC');
                        } catch (InvalidFormatException) {
                            throw new ImportException('Value of archived_at ("'.$record['archived_at'].'") is invalid');
                        }
                    }
                    $this->projectImportHelper->getKey([
                        'name' => $record['name'],
                        'client_id' => $clientId,
                        'organization_id' => $this->organization->id,
                    ], [
                        'color' => isset($record['color']) && $record['color'] !== '' ? $record['color'] : app(ColorService::class)->getRandomColor(),
                        'billable_rate' => isset($record['billable_rate']) && $record['billable_rate'] !== '' ? (int) $record['billable_rate'] : null,
                        'is_public' => isset($record['is_public']) && $record['is_public'] === 'true',
                        'is_billable' => isset($record['billable_default']) && $record['billable_default'] === 'true',
                        'estimated_time' => isset($record['estimated_time']) && $record['estimated_time'] !== '' && is_numeric($record['estimated_time']) && ((int) $record['estimated_time'] !== 0) ? (int) $record['estimated_time'] : null,
                        'archived_at' => $archivedAt,
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

    #[Override]
    public function getName(): string
    {
        return __('importer.generic_projects.name');
    }

    #[Override]
    public function getDescription(): string
    {
        return __('importer.generic_projects.description');
    }
}
