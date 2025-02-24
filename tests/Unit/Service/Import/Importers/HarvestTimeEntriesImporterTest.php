<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\HarvestTimeEntriesImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HarvestTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(HarvestTimeEntriesImporter::class)]
class HarvestTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new HarvestTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('harvest_time_entries_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $users = User::all();
        $this->assertCount(2, $users);
        $user1 = $users->firstWhere('name', 'Peter Tester');
        $this->assertNotNull($user1);
        $this->assertSame(null, $user1->password);
        $this->assertSame('Peter Tester', $user1->name);
        $this->assertSame('peter.tester@solidtime-import.test', $user1->email);
        $members = Member::all();
        $this->assertCount(1, $members);
        $member1 = $members->firstWhere('user_id', $user1->getKey());
        $this->assertNotNull($member1);
        $this->assertSame(Role::Placeholder->value, $member1->role);
        $clients = Client::all();
        $this->assertCount(1, $clients);
        $client1 = $clients->firstWhere('name', 'Big Company');
        $this->assertNotNull($client1);
        $this->assertNull($client1->archived_at);
        $projects = Project::with(['members'])->get();
        $this->assertCount(2, $projects);
        /** @var Project|null $project1 */
        $project1 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project1);
        $this->assertNull($project1->client_id);
        /** @var Project|null $project2 */
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $this->assertSame($client1->getKey(), $project2->client_id);
        $project3 = null;
        // Project without Client
        $this->assertSame(false, $project1->is_public);
        // Project for Big Company
        $this->assertSame(false, $project2->is_public);
        $tasks = Task::all();
        $this->assertCount(1, $tasks);
        $task1 = $tasks->firstWhere('name', 'Task 1');
        $this->assertNotNull($task1);
        $this->assertNull($task1->done_at);
        $this->assertSame($project2->getKey(), $task1->project_id);
        $tags = Tag::all();
        $this->assertCount(0, $tags);

        $timeEntries = TimeEntry::all();
        $this->assertCount(2, $timeEntries);
        $timeEntry1 = $timeEntries->firstWhere('description', '');
        $this->assertNotNull($timeEntry1);
        $this->assertSame('', $timeEntry1->description);
        $this->assertSame('2024-03-03 23:00:00', $timeEntry1->start->toDateTimeString());
        $this->assertSame('2024-03-04 19:00:00', $timeEntry1->end->toDateTimeString());
        $this->assertFalse($timeEntry1->billable);
        $this->assertTrue($timeEntry1->is_imported);
        $this->assertSame([], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-03 23:00:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-03 23:00:36', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertTrue($timeEntry2->is_imported);
        $this->assertSame([], $timeEntry2->tags);

        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
    }
}
