<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ProjectMemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ProjectMemberResource::class)]
class ProjectMemberResourceTest extends FilamentTestCase
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

    private function createProjectMember(?int $billableRate = null): ProjectMember
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->create();
        $project = Project::factory()->forOrganization($organization)->create();

        return ProjectMember::factory()
            ->forProject($project)
            ->forMember($member)
            ->create($billableRate !== null ? ['billable_rate' => $billableRate] : []);
    }

    public function test_can_list_project_members(): void
    {
        // Arrange
        $projectMembers = collect([
            $this->createProjectMember(),
            $this->createProjectMember(),
            $this->createProjectMember(),
        ]);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\ListProjectMembers::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($projectMembers);
        $response->assertCanRenderTableColumn('project.name');
        $response->assertCanRenderTableColumn('user.name');
        $response->assertCanRenderTableColumn('billable_rate');
    }

    public function test_can_sort_project_members_by_billable_rate(): void
    {
        // Arrange
        $projectMember1 = $this->createProjectMember(3000);
        $projectMember2 = $this->createProjectMember(1000);
        $projectMember3 = $this->createProjectMember(2000);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\ListProjectMembers::class)
            ->sortTable('billable_rate');

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords([$projectMember2, $projectMember3, $projectMember1], inOrder: true);
    }

    public function test_can_see_create_page_of_project_member(): void
    {
        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\CreateProjectMember::class);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_edit_page_of_project_member(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()]);

        // Assert
        $response->assertSuccessful();
        $response->assertSchemaStateSet([
            'billable_rate' => 1500,
            'user_id' => $projectMember->user_id,
            'member_id' => $projectMember->member_id,
        ]);
    }

    public function test_can_see_view_page_of_project_member(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember();

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\ViewProjectMembers::class, ['record' => $projectMember->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_update_billable_rate_of_project_member(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->fillForm([
                'billable_rate' => 2500,
            ])
            ->call('save');

        // Assert
        $response->assertHasNoFormErrors();
        $response->assertSuccessful();
        $projectMember->refresh();
        $this->assertSame(2500, $projectMember->billable_rate);
    }

    public function test_can_remove_billable_rate_of_project_member(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->fillForm([
                'billable_rate' => null,
            ])
            ->call('save');

        // Assert
        $response->assertHasNoFormErrors();
        $response->assertSuccessful();
        $projectMember->refresh();
        $this->assertNull($projectMember->billable_rate);
    }

    public function test_update_project_member_fails_if_billable_rate_is_not_positive(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->fillForm([
                'billable_rate' => 0,
            ])
            ->call('save');

        // Assert
        $response->assertHasFormErrors(['billable_rate']);
        $projectMember->refresh();
        $this->assertSame(1500, $projectMember->billable_rate);
    }

    public function test_update_project_member_fails_if_billable_rate_exceeds_maximum(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->fillForm([
                'billable_rate' => 2147483648,
            ])
            ->call('save');

        // Assert
        $response->assertHasFormErrors(['billable_rate']);
        $projectMember->refresh();
        $this->assertSame(1500, $projectMember->billable_rate);
    }

    public function test_update_project_member_fails_if_user_or_member_is_missing(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember(1500);

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->fillForm([
                'user_id' => null,
                'member_id' => null,
            ])
            ->call('save');

        // Assert
        $response->assertHasFormErrors(['user_id', 'member_id']);
    }

    public function test_can_delete_a_project_member(): void
    {
        // Arrange
        $projectMember = $this->createProjectMember();

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\EditProjectMember::class, ['record' => $projectMember->getKey()])
            ->callAction('delete');

        // Assert
        $response->assertHasNoActionErrors();
        $response->assertSuccessful();
        $this->assertDatabaseMissing(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
    }

    public function test_can_bulk_delete_project_members(): void
    {
        // Arrange
        $projectMember1 = $this->createProjectMember();
        $projectMember2 = $this->createProjectMember();
        $projectMember3 = $this->createProjectMember();

        // Act
        $response = Livewire::test(ProjectMemberResource\Pages\ListProjectMembers::class)
            ->callTableBulkAction('delete', [$projectMember1, $projectMember2]);

        // Assert
        $response->assertHasNoTableBulkActionErrors();
        $response->assertSuccessful();
        $this->assertDatabaseMissing(ProjectMember::class, ['id' => $projectMember1->getKey()]);
        $this->assertDatabaseMissing(ProjectMember::class, ['id' => $projectMember2->getKey()]);
        $this->assertDatabaseHas(ProjectMember::class, ['id' => $projectMember3->getKey()]);
    }
}
