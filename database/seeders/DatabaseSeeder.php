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
        $userAcmeOwner = User::factory()->create([
            'name' => 'ACME Admin',
            'email' => 'owner@acme.test',
        ]);
        $organizationAcme = Organization::factory()->withOwner($userAcmeOwner)->create([
            'name' => 'ACME Corp',
        ]);
        $userAcmeManager = User::factory()->withPersonalOrganization()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $userAcmeAdmin = User::factory()->withPersonalOrganization()->create([
            'name' => 'ACME Admin',
            'email' => 'admin@acme.test',
        ]);
        $userAcmeEmployee = User::factory()->withPersonalOrganization()->create([
            'name' => 'Max Mustermann',
            'email' => 'max.mustermann@acme.test',
        ]);
        $userAcmePlaceholder = User::factory()->placeholder()->create([
            'name' => 'Old Employee',
            'email' => 'old.employee@acme.test',
            'password' => null,
        ]);
        $userAcmeManager->organizations()->attach($organizationAcme, [
            'role' => 'manager',
        ]);
        $userAcmeAdmin->organizations()->attach($organizationAcme, [
            'role' => 'admin',
        ]);
        $userAcmeEmployee->organizations()->attach($organizationAcme, [
            'role' => 'employee',
        ]);
        $userAcmePlaceholder->organizations()->attach($organizationAcme, [
            'role' => 'employee',
        ]);

        $timeEntriesAcmeAdmin = TimeEntry::factory()
            ->count(10)
            ->forUser($userAcmeAdmin)
            ->forOrganization($organizationAcme)
            ->create();
        $timeEntriesAcmePlaceholder = TimeEntry::factory()
            ->count(10)
            ->forUser($userAcmePlaceholder)
            ->forOrganization($organizationAcme)
            ->create();
        $timeEntriesAcmePlaceholder = TimeEntry::factory()
            ->count(10)
            ->forUser($userAcmeEmployee)
            ->forOrganization($organizationAcme)
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
        $userAcmeManager = User::factory()->withPersonalOrganization()->create([
            'name' => 'Other User',
            'email' => 'test@rival-company.test',
        ]);
        $userAcmeManager->organizations()->attach($organization2, [
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
