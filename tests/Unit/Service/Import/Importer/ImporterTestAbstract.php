<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importer;

use App\Models\Client;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $clients = Client::all();
        $this->assertCount(1, $clients);
        $client1 = $clients->firstWhere('name', 'Big Company');
        $this->assertNotNull($client1);
        $projects = Project::with(['members'])->get();
        $this->assertCount(2, $projects);
        $project1 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project1);
        $this->assertNull($project1->client_id);
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $this->assertSame($client1->getKey(), $project2->client_id);
        if ($detailed) {
            $this->assertSame(10001, $project2->billable_rate);
            $projectMembersOfProject2 = $project2->members;
            $this->assertCount(1, $projectMembersOfProject2);
            $this->assertSame($user1->getKey(), $projectMembersOfProject2->first()->user_id);
            $this->assertSame(10002, $projectMembersOfProject2->first()->billable_rate);
        }
        $tasks = Task::all();
        $this->assertCount(1, $tasks);
        $task1 = $tasks->firstWhere('name', 'Task 1');
        $this->assertNotNull($task1);
        $this->assertSame($project2->getKey(), $task1->project_id);
        $tags = Tag::all();
        $this->assertCount(2, $tags);
        $tag1 = $tags->firstWhere('name', 'Development');
        $tag2 = $tags->firstWhere('name', 'Backend');
        $this->assertNotNull($tag1);

        return (object) [
            'user1' => $user1,
            'project1' => $project1,
            'project2' => $project2,
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
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $this->assertSame(10001, $project2->billable_rate);
        $this->assertSame($client1->getKey(), $project2->client_id);
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
}
