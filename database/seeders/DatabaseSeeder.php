<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->deleteAll();
        $organization = Team::factory()->create([
            'name' => 'ACME Corp',
        ]);
        $user1 = User::factory()->withPersonalTeam()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user1->teams()->attach($organization);
        $client = Client::factory()->create([
            'name' => 'Big Company',
        ]);
        $bigCompanyProject = Project::factory()->forClient($client)->create([
            'name' => 'Big Company Project',
        ]);
        Task::factory()->forProject($bigCompanyProject)->create();

        $internalProject = Project::factory()->create([
            'name' => 'Internal Project',
        ]);
    }

    private function deleteAll(): void
    {
        DB::table((new TimeEntry())->getTable())->delete();
        DB::table((new Task())->getTable())->delete();
        DB::table((new Project())->getTable())->delete();
        DB::table((new Client())->getTable())->delete();
        DB::table((new User())->getTable())->delete();
        DB::table((new Team())->getTable())->delete();
    }
}
