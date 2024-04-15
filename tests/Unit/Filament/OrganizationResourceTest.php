<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

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
            'user_id' => $user->id,
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
}
