<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\DeletionService;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(OrganizationResource::class)]
class OrganizationResourceTest extends FilamentTestCase
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

    public function test_can_list_organizations(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organizations = Organization::factory()->state([
            'user_id' => $user->getKey(),
        ])->createMany(5);

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($organizations);
    }

    public function test_can_see_edit_page_of_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = Livewire::test(OrganizationResource\Pages\EditOrganization::class, ['record' => $organization->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_delete_a_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->mock(DeletionService::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('deleteOrganization')
                ->withArgs(fn (Organization $organizationArg) => $organizationArg->is($user->organization))
                ->once();
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\EditOrganization::class, ['record' => $user->organization->getKey()])
            ->callAction('delete')
            ->assertHasNoActionErrors();

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_list_related_users(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organization->users()->attach($user1);
        $organization->users()->attach($user2);

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($organization->users()->get());
    }

    public function test_can_list_related_invitations(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $organizationInvitations = OrganizationInvitation::factory()->forOrganization($organization)->createMany(5);

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\InvitationsRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($organizationInvitations);
    }
}
