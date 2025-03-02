<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Member;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Tests\TestCase;
use ZipArchive;

class ImporterTestAbstract extends TestCase
{
    use RefreshDatabase;

    /**
     * @return object{user1: User, project1: Project, project2: Project, tag1: Tag, tag2: Tag}
     */
    protected function checkTestScenarioAfterImportExcludingTimeEntries(bool $detailed = false): object
    {
        $users = User::all();
        $this->assertCount(2, $users);
        $user1 = $users->firstWhere('name', 'Peter Tester');
        $this->assertNotNull($user1);
        $this->assertSame(null, $user1->password);
        $this->assertSame('Peter Tester', $user1->name);
        $this->assertSame('peter.test@email.test', $user1->email);
        $members = Member::all();
        $this->assertCount(1, $members);
        $member1 = $members->firstWhere('user_id', $user1->getKey());
        $this->assertNotNull($member1);
        $this->assertSame(Role::Placeholder->value, $member1->role);
        $clients = Client::all();
        if ($detailed) {
            $this->assertCount(2, $clients);
            $client1 = $clients->firstWhere('name', 'Big Company');
            $this->assertNotNull($client1);
            $this->assertNull($client1->archived_at);
            $client2 = $clients->firstWhere('name', 'Other Company (Archived)');
            $this->assertNotNull($client2);
            $this->assertNotNull($client2->archived_at);
        } else {
            $this->assertCount(1, $clients);
            $client1 = $clients->firstWhere('name', 'Big Company');
            $this->assertNotNull($client1);
            $this->assertNull($client1->archived_at);
        }
        $projects = Project::with(['members'])->get();
        if ($detailed) {
            $this->assertCount(3, $projects);
        } else {
            $this->assertCount(2, $projects);
        }
        /** @var Project|null $project1 */
        $project1 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project1);
        $this->assertNull($project1->client_id);
        /** @var Project|null $project2 */
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $this->assertSame($client1->getKey(), $project2->client_id);
        $project3 = null;
        if ($detailed) {
            /** @var Project|null $project3 */
            $project3 = $projects->firstWhere('name', 'Project (Archived)');
            $this->assertNotNull($project3);
            // Project without Client
            $this->assertSame(false, $project1->is_billable);
            $this->assertSame(false, $project1->is_public);
            $this->assertSame('#ef5350', $project1->color);
            $this->assertSame(null, $project1->billable_rate);
            // Project for Big Company
            $this->assertSame(true, $project2->is_billable);
            $this->assertSame(false, $project2->is_public);
            $this->assertSame('#ec407a', $project2->color);
            $this->assertSame(10001, $project2->billable_rate);
            // Project (Archived)
            $this->assertSame(true, $project3->is_billable);
            $this->assertSame(true, $project3->is_public);
            $this->assertSame('#6a407f', $project3->color);
            $this->assertSame(null, $project3->billable_rate);
            $this->assertSame($client2->getKey(), $project3->client_id);
            // Project members
            $projectMembersOfProject2 = $project2->members;
            $this->assertCount(1, $projectMembersOfProject2);
            $this->assertSame($user1->getKey(), $projectMembersOfProject2->first()->user_id);
            $this->assertSame(10002, $projectMembersOfProject2->first()->billable_rate);
        } else {
            // Project without Client
            $this->assertSame(false, $project1->is_public);
            // Project for Big Company
            $this->assertSame(false, $project2->is_public);
        }
        $tasks = Task::all();
        if ($detailed) {
            $this->assertCount(2, $tasks);
        } else {
            $this->assertCount(1, $tasks);
        }
        $task1 = $tasks->firstWhere('name', 'Task 1');
        $this->assertNotNull($task1);
        $this->assertNull($task1->done_at);
        $this->assertSame($project2->getKey(), $task1->project_id);
        if ($detailed) {
            $task2 = $tasks->firstWhere('name', 'Task 2');
            $this->assertNotNull($task1);
            $this->assertSame($project2->getKey(), $task2->project_id);
            $this->assertNotNull($task2->done_at);
        }
        $tags = Tag::all();
        $this->assertCount(2, $tags);
        $tag1 = $tags->firstWhere('name', 'Development');
        $tag2 = $tags->firstWhere('name', 'Backend');
        $this->assertNotNull($tag1);

        return (object) [
            'user1' => $user1,
            'project1' => $project1,
            'project2' => $project2,
            'project3' => $project3,
            'tag1' => $tag1,
            'tag2' => $tag2,
        ];
    }

    /**
     * @return object{client1: Client, project1: Project, project2: Project, task1: Task}
     */
    protected function checkTestScenarioProjectsOnlyAfterImport(): object
    {
        $clients = Client::all();
        $this->assertCount(1, $clients);
        $client1 = $clients->firstWhere('name', 'Big Company');
        $this->assertNotNull($client1);
        $projects = Project::all();
        $this->assertCount(2, $projects);
        $project1 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project1);
        $this->assertNull($project1->client_id);
        $this->assertSame(null, $project1->estimated_time);
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $this->assertSame(10001, $project2->billable_rate);
        $this->assertSame($client1->getKey(), $project2->client_id);
        $this->assertSame(3603996, $project2->estimated_time);
        $tasks = Task::all();
        $this->assertCount(3, $tasks);
        $task1 = $tasks->firstWhere('name', 'Task 1');
        $this->assertNotNull($task1);
        $this->assertSame($project2->getKey(), $task1->project_id);
        $task2 = $tasks->firstWhere('name', 'Task 2');
        $this->assertNotNull($task2);
        $this->assertSame($project2->getKey(), $task2->project_id);
        $task3 = $tasks->firstWhere('name', 'Task 3');
        $this->assertNotNull($task3);
        $this->assertSame($project2->getKey(), $task3->project_id);

        return (object) [
            'client1' => $client1,
            'project1' => $project1,
            'project2' => $project2,
            'task1' => $task1,
        ];
    }

    /**
     * @param  object{user1: User, project1: Project, project2: Project, tag1: Tag, tag2: Tag}  $testScenario
     */
    protected function checkTimeEntries(object $testScenario, bool $secondRun = false): void
    {
        $timeEntries = TimeEntry::all();
        if ($secondRun) {
            $this->assertCount(4, $timeEntries);
        } else {
            $this->assertCount(2, $timeEntries);
        }
        $timeEntry1 = $timeEntries->firstWhere('description', '');
        $this->assertNotNull($timeEntry1);
        $this->assertSame('', $timeEntry1->description);
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->start->toDateTimeString());
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->end->toDateTimeString());
        $this->assertFalse($timeEntry1->billable);
        $this->assertTrue($timeEntry1->is_imported);
        $this->assertSame([$testScenario->tag1->getKey(), $testScenario->tag2->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 09:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertTrue($timeEntry2->is_imported);
        $this->assertSame([], $timeEntry2->tags);
    }

    protected function createTestZip(string $folder): string
    {
        $tempDir = TemporaryDirectory::make();
        $zipPath = $tempDir->path('test.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach (Storage::disk('testfiles')->allFiles($folder) as $file) {
            $zip->addFile(Storage::disk('testfiles')->path($file), Str::of($file)->after($folder.'/')->value());
        }
        $zip->close();

        return $zipPath;
    }
}
