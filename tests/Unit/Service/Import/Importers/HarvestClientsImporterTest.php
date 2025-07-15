<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\HarvestClientsImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HarvestClientsImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
class HarvestClientsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new HarvestClientsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('harvest_clients_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $clients = Client::query()->whereBelongsTo($organization, 'organization')->get();
        $this->assertCount(2, $clients);
        $client1 = $clients->where('name', 'Example Client')->first();
        $this->assertNotNull($client1);
        // Client name in Harvest: \\ 🔥 Special characters """`!@#$%^&*()_+\-=\[\]{};':"\\|,.''<>\/?~ \\\
        $client2 = $clients->where('name', '\\\\ 🔥 Special characters  """`!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.\'\'<>\/?~ \\\\\\')->first();
        $this->assertNotNull($client2);
    }
}
