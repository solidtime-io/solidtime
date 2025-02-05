<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\OrganizationInvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(OrganizationInvitationResource::class)]
class OrganizationInvitationResourceTest extends FilamentTestCase
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

    public function test_can_list_organization_invitations(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->withOwner($user)->create();
        $organizationInvitations = OrganizationInvitation::factory()->forOrganization($organization)->createMany(5);

        // Act
        $response = Livewire::test(OrganizationInvitationResource\Pages\ListOrganizationInvitations::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($organizationInvitations);
    }

    public function test_can_see_edit_page_of_organization_invitation(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $organizationInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create();

        // Act
        $response = Livewire::test(OrganizationInvitationResource\Pages\EditOrganizationInvitation::class, [
            'record' => $organizationInvitation->getKey(),
        ]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_delete_a_organization_invitation(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $organizationInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create();

        // Act
        $response = Livewire::test(OrganizationInvitationResource\Pages\EditOrganizationInvitation::class, [
            'record' => $organizationInvitation->getKey(),
        ])->callAction(DeleteAction::class);

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseMissing(OrganizationInvitation::class, [
            'id' => $organizationInvitation->getKey(),
        ]);
    }
}
