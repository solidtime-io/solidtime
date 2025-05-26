<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Service\ColorService;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\GenericProjectsImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(GenericProjectsImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(GenericProjectsImporter::class)]
class GenericProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new GenericProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('generic_projects_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $clients = Client::all();
        $this->assertCount(2, $clients);
        $client1 = $clients->firstWhere('name', 'Big Company');
        $this->assertNotNull($client1);
        $client2 = $clients->firstWhere('name', 'Some client');
        $this->assertNotNull($client2);
        $projects = Project::all();
        $this->assertCount(3, $projects);
        // Project 1
        $project1 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project1);
        $this->assertTrue(app(ColorService::class)->isBuiltInColor($project1->color));
        $this->assertSame(10001, $project1->billable_rate);
        $this->assertFalse($project1->is_public);
        $this->assertSame($client1->getKey(), $project1->client_id);
        $this->assertTrue($project1->is_billable);
        $this->assertSame(null, $project1->estimated_time);
        $this->assertNull($project1->archived_at);
        // Project 2
        $project2 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project2);
        $this->assertSame('#ef5350', $project2->color);
        $this->assertSame(null, $project2->billable_rate);
        $this->assertFalse($project2->is_public);
        $this->assertSame(null, $project2->client_id);
        $this->assertFalse($project2->is_billable);
        $this->assertSame(1000, $project2->estimated_time);
        $this->assertSame(null, $project2->archived_at);
        $project3 = $projects->firstWhere('name', 'Project (Archived)');
        $this->assertNotNull($project3);
        $this->assertSame('#6a407f', $project3->color);
        $this->assertSame(null, $project3->billable_rate);
        $this->assertTrue($project3->is_public);
        $this->assertSame($client2->getKey(), $project3->client_id);
        $this->assertTrue($project3->is_billable);
        $this->assertSame(null, $project3->estimated_time);
        $this->assertSame('2024-08-25T10:00:00Z', $project3->archived_at->toIso8601ZuluString());

        $tasks = Task::all();
        $this->assertCount(0, $tasks);

        $this->assertSame(0, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(0, $report->tasksCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(3, $report->projectsCreated);
        $this->assertSame(2, $report->clientsCreated);
    }
}
