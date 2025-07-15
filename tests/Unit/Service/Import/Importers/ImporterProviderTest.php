<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Service\Import\Importers\ClockifyProjectsImporter;
use App\Service\Import\Importers\ImporterProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ImporterProvider::class)]
class ImporterProviderTest extends TestCase
{
    public function test_register_importer_can_register_a_new_importer_for_example_in_an_extension(): void
    {
        // Arrange
        $provider = new ImporterProvider;

        // Act
        $provider->registerImporter('some_provider_importer', ClockifyProjectsImporter::class);

        // Assert
        $importer = $provider->getImporter('some_provider_importer');
        $this->assertSame(ClockifyProjectsImporter::class, $importer::class);
    }

    public function test_get_importer_keys_return_the_keys_of_the_available_importers(): void
    {
        // Arrange
        $provider = new ImporterProvider;

        // Act
        $keys = $provider->getImporterKeys();

        // Assert
        $this->assertSame([
            'toggl_time_entries',
            'toggl_data_importer',
            'clockify_time_entries',
            'clockify_projects',
            'solidtime',
            'harvest_projects',
            'harvest_time_entries',
            'harvest_clients',
            'generic_projects',
            'generic_time_entries',
        ], $keys);
    }
}
