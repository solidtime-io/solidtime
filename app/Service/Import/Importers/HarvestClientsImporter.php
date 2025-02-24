<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class HarvestClientsImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    private const array REQUIRED_FIELDS = [
        'Client Name',
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
                $this->clientImportHelper->getKey([
                    'name' => $record['Client Name'],
                    'organization_id' => $this->organization->id,
                ]);
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
        return __('importer.harvest_clients.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.harvest_clients.description');
    }
}
