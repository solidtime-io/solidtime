<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

class ImporterProvider
{
    private array $importers = [
        'toggl_time_entries' => TogglTimeEntriesImporter::class,
    ];

    /**
     * @param  class-string<ImporterContract>  $importer
     */
    public function registerImporter(string $type, string $importer): void
    {
        $this->importers[$type] = $importer;
    }

    /**
     * @return array<string>
     */
    public function getImporterKeys(): array
    {
        return array_keys($this->importers);
    }

    public function getImporter(string $type): ImporterContract
    {
        if (! array_key_exists($type, $this->importers)) {
            throw new \InvalidArgumentException('Invalid importer type');
        }

        return new $this->importers[$type];
    }
}
