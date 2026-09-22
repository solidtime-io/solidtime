<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ProjectMemberResource;
use App\Filament\Resources\ProjectResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ProjectResource::class)]
class ProjectResourceTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);
    }

    public function test_can_list_projects(): void
    {
        // Arrange
        $projects = Project::factory()->createMany(5);

        // Act
        $response = Livewire::test(ProjectResource\Pages\ListProjects::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($projects);
    }

    public function test_can_see_edit_page_of_project(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act
        $response = Livewire::test(ProjectResource\Pages\EditProject::class, ['record' => $project->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_list_related_project_members(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $otherProject = Project::factory()->forOrganization($organization)->create();
        $projectMembers = collect();
        foreach (range(1, 3) as $i) {
            $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
            $projectMembers->push(ProjectMember::factory()->forProject($project)->forMember($member)->create());
        }
        $otherMember = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
        $otherProjectMember = ProjectMember::factory()->forProject($otherProject)->forMember($otherMember)->create();

        // Act
        $response = Livewire::test(ProjectResource\RelationManagers\ProjectMembersRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($projectMembers);
        $response->assertCanNotSeeTableRecords([$otherProjectMember]);
        $response->assertCanRenderTableColumn('user.name');
        $response->assertCanRenderTableColumn('billable_rate');
    }

    public function test_related_project_members_have_view_and_edit_actions_linking_to_project_member_resource(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $member = Member::factory()->forOrganization($organization)->forUser(User::factory()->create())->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create();

        // Act
        $response = Livewire::test(ProjectResource\RelationManagers\ProjectMembersRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ProjectResource\Pages\EditProject::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertTableActionHasUrl('view', ProjectMemberResource::getUrl('view', [
            'record' => $projectMember->getKey(),
        ]), $projectMember);
        $response->assertTableActionHasUrl('edit', ProjectMemberResource::getUrl('edit', [
            'record' => $projectMember->getKey(),
        ]), $projectMember);
    }
}
