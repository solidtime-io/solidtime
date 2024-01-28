<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
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
        $organization1 = Organization::factory()->create([
            'name' => 'ACME Corp',
        ]);
        $user1 = User::factory()->withPersonalOrganization()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $employee1 = User::factory()->withPersonalOrganization()->create([
            'name' => 'Test User',
            'email' => 'employee@example.com',
        ]);
        $userAcmeAdmin = User::factory()->create([
            'name' => 'ACME Admin',
            'email' => 'admin@acme.test',
        ]);
        $user1->organizations()->attach($organization1, [
            'role' => 'manager',
        ]);
        $userAcmeAdmin->organizations()->attach($organization1, [
            'role' => 'admin',
        ]);
        $timeEntriesEmployees = TimeEntry::factory()
            ->count(10)
            ->forUser($employee1)
            ->forOrganization($organization1)
            ->create();
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

        $organization2 = Organization::factory()->create([
            'name' => 'Rival Corp',
        ]);
        $user1 = User::factory()->withPersonalOrganization()->create([
            'name' => 'Other User',
            'email' => 'test@rival-company.test',
        ]);
        $user1->organizations()->attach($organization2, [
            'role' => 'admin',
        ]);
        $otherCompanyProject = Project::factory()->forClient($client)->create([
            'name' => 'Scale Company',
        ]);

        User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);
    }

    private function deleteAll(): void
    {
        DB::table((new TimeEntry())->getTable())->delete();
        DB::table((new Task())->getTable())->delete();
        DB::table((new Project())->getTable())->delete();
        DB::table((new Client())->getTable())->delete();
        DB::table((new User())->getTable())->delete();
        DB::table((new Organization())->getTable())->delete();
    }
}
