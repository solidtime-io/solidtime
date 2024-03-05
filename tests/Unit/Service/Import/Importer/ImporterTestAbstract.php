<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importer;

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
     * @return object{user1: User, project1: Project, project2: Project, tag1: Tag}
     */
    protected function checkTestScenarioAfterImportExcludingTimeEntries(): object
    {
        $users = User::all();
        $this->assertCount(2, $users);
        $user1 = $users->firstWhere('name', 'Peter Tester');
        $this->assertNotNull($user1);
        $this->assertSame(null, $user1->password);
        $this->assertSame('Peter Tester', $user1->name);
        $this->assertSame('peter.test@email.test', $user1->email);
        $projects = Project::all();
        $this->assertCount(2, $projects);
        $project1 = $projects->firstWhere('name', 'Project without Client');
        $this->assertNotNull($project1);
        $project2 = $projects->firstWhere('name', 'Project for Big Company');
        $this->assertNotNull($project2);
        $tasks = Task::all();
        $this->assertCount(1, $tasks);
        $task1 = $tasks->firstWhere('name', 'Task 1');
        $this->assertNotNull($task1);
        $this->assertSame($project2->getKey(), $task1->project_id);
        $tags = Tag::all();
        $this->assertCount(1, $tags);
        $tag1 = $tags->firstWhere('name', 'Development');
        $this->assertNotNull($tag1);

        return (object) [
            'user1' => $user1,
            'project1' => $project1,
            'project2' => $project2,
            'tag1' => $tag1,
        ];
    }
}
