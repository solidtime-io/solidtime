<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

class ImporterProvider
{
    /**
     * @var array<string, class-string<ImporterContract>>
     */
    private array $importers = [
        'toggl_time_entries' => TogglTimeEntriesImporter::class,
        'toggl_data_importer' => TogglDataImporter::class,
        'clockify_time_entries' => ClockifyTimeEntriesImporter::class,
        'clockify_projects' => ClockifyProjectsImporter::class,
        'solidtime' => SolidtimeImporter::class,
        'harvest_projects' => HarvestProjectsImporter::class,
        'harvest_time_entries' => HarvestTimeEntriesImporter::class,
        'harvest_clients' => HarvestClientsImporter::class,
        'generic_projects' => GenericProjectsImporter::class,
        'generic_time_entries' => GenericTimeEntriesImporter::class,
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

    /**
     * @return array<string, class-string<ImporterContract>>
     */
    public function getImporters(): array
    {
        return $this->importers;
    }

    public function getImporter(string $type): ImporterContract
    {
        if (! array_key_exists($type, $this->importers)) {
            throw new \InvalidArgumentException('Invalid importer type');
        }

        return new $this->importers[$type];
    }
}
