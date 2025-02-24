<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\HarvestProjectsImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HarvestProjectsImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(HarvestProjectsImporter::class)]
class HarvestProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new HarvestProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('harvest_projects_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $clients = Client::query()->whereBelongsTo($organization, 'organization')->get();
        $this->assertCount(2, $clients);
        /** @var Client|null $client1 */
        $client1 = $clients->where('name', 'Example Client')->first();
        $this->assertNotNull($client1);
        // Client name in Harvest: \\ 🔥 Special characters client """`!@#$%^&*()_+\-=\[\]{};':"\\|,.''<>\/?~ \\\
        /** @var Client|null $client2 */
        $client2 = $clients->where('name', '\\\\ 🔥 Special characters client """`!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.\'\'<>\/?~ \\\\\\')->first();
        $this->assertNotNull($client2);

        $projects = Project::query()->whereBelongsTo($organization, 'organization')->get();
        $this->assertCount(2, $projects);
        /** @var Project|null $project1 */
        $project1 = $projects->where('name', 'Example Project')->first();
        $this->assertNotNull($project1);
        $this->assertSame($client1->getKey(), $project1->client_id);
        $this->assertSame(50 * 60 * 60, $project1->estimated_time); // 50h
        $this->assertSame(true, $project1->is_billable);
        /** @var Project|null $project2 */
        $project2 = $projects->where('name', '\\\\ 🔥 Special characters project """`!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.\'\'<>\/?~ \\\\\\')->first();
        $this->assertNotNull($project2);
        $this->assertSame($client2->getKey(), $project2->client_id);
        $this->assertSame(null, $project2->estimated_time);
        $this->assertSame(false, $project2->is_billable);
    }
}
