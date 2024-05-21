<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
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
        $userWithMultipleOrganizations = User::factory()->withPersonalOrganization()->create([
            'name' => 'Mister Overemployed',
            'email' => 'overemployed@acme.test',
        ]);

        $userAcmeOwner = User::factory()->withPersonalOrganization()->create([
            'name' => 'Acme Owner',
            'email' => 'owner@acme.test',
        ]);
        $organizationAcme = Organization::factory()->withOwner($userAcmeOwner)->create([
            'name' => 'ACME Corp',
            'personal_team' => false,
            'currency' => 'EUR',
        ]);
        $userRivalManager = User::factory()->withPersonalOrganization()->create([
            'name' => 'Acme Manager',
            'email' => 'test@example.com',
        ]);
        $userAcmeAdmin = User::factory()->withPersonalOrganization()->create([
            'name' => 'Acme Admin',
            'email' => 'admin@acme.test',
        ]);
        $userAcmeEmployee = User::factory()->withPersonalOrganization()->create([
            'name' => 'Acme Employee',
            'email' => 'max.mustermann@acme.test',
        ]);
        $userAcmePlaceholder = User::factory()->placeholder()->create([
            'name' => 'Acme Placeholder',
            'email' => 'old.employee@acme.test',
            'password' => null,
        ]);
        $userAcmeOwnerMember = Member::factory()->forUser($userAcmeOwner)->forOrganization($organizationAcme)->role(Role::Owner)->create();
        $userAcmeManagerMember = Member::factory()->forUser($userRivalManager)->forOrganization($organizationAcme)->role(Role::Manager)->create();
        $userAcmeAdminMember = Member::factory()->forUser($userAcmeAdmin)->forOrganization($organizationAcme)->role(Role::Admin)->create();
        $userAcmeEmployeeMember = Member::factory()->forUser($userAcmeEmployee)->forOrganization($organizationAcme)->role(Role::Employee)->create();
        $userAcmePlaceholderMember = Member::factory()->forUser($userAcmePlaceholder)->forOrganization($organizationAcme)->role(Role::Placeholder)->create();
        $userWithMultipleOrganizationsAcmeMember = Member::factory()->forUser($userWithMultipleOrganizations)->forOrganization($organizationAcme)->role(Role::Employee)->create();

        TimeEntry::factory()
            ->count(10)
            ->forMember($userAcmeAdminMember)
            ->create();
        TimeEntry::factory()
            ->count(10)
            ->forMember($userAcmePlaceholderMember)
            ->create();
        TimeEntry::factory()
            ->count(10)
            ->forMember($userAcmeEmployeeMember)
            ->create();
        TimeEntry::factory()
            ->count(5)
            ->forMember($userWithMultipleOrganizationsAcmeMember)
            ->create();
        $client = Client::factory()->forOrganization($organizationAcme)->create([
            'name' => 'Big Company',
        ]);
        $bigCompanyProject = Project::factory()->forOrganization($organizationAcme)->forClient($client)->create([
            'name' => 'Big Company Project',
        ]);
        ProjectMember::factory()->forProject($bigCompanyProject)->forMember($userAcmeEmployeeMember)->create();
        ProjectMember::factory()->forProject($bigCompanyProject)->forMember($userAcmeAdminMember)->create();
        ProjectMember::factory()->forProject($bigCompanyProject)->forMember($userWithMultipleOrganizationsAcmeMember)->create();

        Task::factory()->forOrganization($organizationAcme)->forProject($bigCompanyProject)->create();

        $internalProject = Project::factory()->forOrganization($organizationAcme)->create([
            'name' => 'Internal Project',
        ]);

        $organization2Owner = User::factory()->create([
            'name' => 'Other Owner',
            'email' => 'owner@rival-company.test',
        ]);
        $organizationRival = Organization::factory()->withOwner($organization2Owner)->create([
            'name' => 'Rival Corp',
            'personal_team' => true,
            'currency' => 'USD',
        ]);
        $userRivalManager = User::factory()->withPersonalOrganization()->create([
            'name' => 'Other User',
            'email' => 'test@rival-company.test',
        ]);
        $userRivalManagerMember = Member::factory()->forUser($userRivalManager)->forOrganization($organizationRival)->role(Role::Admin)->create();
        $userWithMultipleOrganizationsRivalMember = Member::factory()->forUser($userWithMultipleOrganizations)->forOrganization($organizationRival)->role(Role::Employee)->create();
        $otherCompanyProject = Project::factory()->forOrganization($organizationRival)->forClient($client)->create([
            'name' => 'Scale Company',
        ]);
        ProjectMember::factory()->forProject($otherCompanyProject)->forMember($userRivalManagerMember)->create();
        ProjectMember::factory()->forProject($otherCompanyProject)->forMember($userWithMultipleOrganizationsRivalMember)->create();
        TimeEntry::factory()
            ->count(5)
            ->forMember($userWithMultipleOrganizationsRivalMember)
            ->create();

        User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);
    }

    private function deleteAll(): void
    {
        DB::table((new TimeEntry())->getTable())->delete();
        DB::table((new Task())->getTable())->delete();
        DB::table((new Tag())->getTable())->delete();
        DB::table((new ProjectMember())->getTable())->delete();
        DB::table((new Project())->getTable())->delete();
        DB::table((new Client())->getTable())->delete();
        DB::table((new User())->getTable())->delete();
        DB::table((new Organization())->getTable())->delete();
    }
}
