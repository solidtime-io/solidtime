<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Enums\Role;
use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\UserResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DeletionService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(UserResource::class)]
class UserResourceTest extends FilamentTestCase
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

    public function test_can_list_users(): void
    {
        // Arrange
        $users = User::factory()->createMany(5);

        // Act
        $response = Livewire::test(UserResource\Pages\ListUsers::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($users);
    }

    public function test_can_see_edit_page_of_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_view_page_of_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = Livewire::test(UserResource\Pages\ViewUser::class, ['record' => $user->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_create_page_of_user(): void
    {
        // Act
        $response = Livewire::test(UserResource\Pages\CreateUser::class);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_create_user(): void
    {
        // Arrange
        $userFake = User::factory()->make();

        // Act
        $response = Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => $userFake->name,
                'email' => $userFake->email,
                'password_create' => 'password',
                'timezone' => $userFake->timezone,
                'week_start' => $userFake->week_start->value,
                'currency' => 'EUR',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $user = User::where('email', $userFake->email)->first();
        $this->assertNotNull($user);
        $this->assertSame($userFake->name, $user->name);
        $this->assertSame($userFake->email, $user->email);
        $this->assertSame($userFake->timezone, $user->timezone);
        $this->assertSame($userFake->week_start->value, $user->week_start->value);
        $organization = $user->ownedOrganizations()->first();
        $this->assertNotNull($organization);
        $this->assertSame('EUR', $organization->currency);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_can_delete_a_user(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->mock(DeletionService::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('deleteUser')
                ->withArgs(fn (User $userArg) => $userArg->is($user->user))
                ->once();
        });

        // Act
        $response = Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->user->getKey()])
            ->callAction('delete');

        // Assert
        $response->assertHasNoActionErrors();
        $response->assertSuccessful();
    }

    public function test_delete_user_shows_error_notification_on_failure(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->mock(DeletionService::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('deleteUser')
                ->withArgs(fn (User $userArg) => $userArg->is($user->user))
                ->andThrow(new CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers);
        });

        // Act
        $response = Livewire::test(UserResource\Pages\EditUser::class, ['record' => $user->user->getKey()])
            ->callAction('delete');

        // Assert
        $response->assertNotified(__('exceptions.api.can_not_delete_user_who_is_owner_of_organization_with_multiple_members'));
        $response->assertSuccessful();
    }

    public function test_can_list_related_organizations(): void
    {
        // Arrange
        $user = User::factory()->create();
        $ownedOrganization = Organization::factory()->withOwner($user)->create();
        Member::factory()->forOrganization($ownedOrganization)->forUser($user)->role(Role::Owner)->create();
        $organization = Organization::factory()->create();
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($user->organizations()->get());
        $response->assertCanSeeTableRecords($user->ownedOrganizations()->get());
    }

    public function test_can_list_related_owned_organizations(): void
    {
        // Arrange
        $user = User::factory()->create();
        $ownedOrganization = Organization::factory()->withOwner($user)->create();
        Member::factory()->forOrganization($ownedOrganization)->forUser($user)->role(Role::Owner)->create();
        $organization = Organization::factory()->create();
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OwnedOrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($user->ownedOrganizations()->get());
        $response->assertCanNotSeeTableRecords([$organization]);
    }

    public function test_related_organizations_have_view_action_linking_to_organization_resource(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertTableActionHasUrl('view', OrganizationResource::getUrl('view', [
            'record' => $organization->getKey(),
        ]), $organization);
    }

    public function test_can_edit_role_of_related_organization(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('edit', $organization, data: [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertSame(Role::Admin->value, $member->refresh()->role);
    }

    public function test_can_make_user_owner_of_related_organization(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('edit', $organization, data: [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertSame(Role::Owner->value, $member->refresh()->role);
        $this->assertSame(Role::Admin->value, $ownerMember->refresh()->role);
        $this->assertSame($user->getKey(), $organization->refresh()->user_id);
    }

    public function test_edit_related_organization_shows_error_notification_if_role_of_owner_is_changed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('edit', $organization, data: [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->danger()
                ->title('Update failed')
                ->body(__('exceptions.api.organization_needs_at_least_one_owner'))
                ->persistent()
        );
        $this->assertSame(Role::Owner->value, $ownerMember->refresh()->role);
    }

    public function test_edit_related_organization_does_not_change_role_if_role_is_unchanged_for_owner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('edit', $organization, data: [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $response->assertNotNotified(
            Notification::make()
                ->danger()
                ->title('Update failed')
                ->body(__('exceptions.api.organization_needs_at_least_one_owner'))
                ->persistent()
        );
        $this->assertSame(Role::Owner->value, $ownerMember->refresh()->role);
    }

    public function test_edit_related_organization_does_not_change_role_if_role_is_unchanged_for_placeholder(): void
    {
        // Arrange
        $user = User::factory()->placeholder()->create();
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Placeholder)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('edit', $organization, data: [
            'role' => Role::Placeholder->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $response->assertNotNotified(
            Notification::make()
                ->danger()
                ->title('Update failed')
                ->body(__('exceptions.api.changing_role_of_placeholder_is_not_allowed'))
                ->persistent()
        );
        $this->assertSame(Role::Placeholder->value, $member->refresh()->role);
    }

    public function test_can_detach_related_organization_from_user(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('detach', $organization);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing(Member::class, [
            'id' => $member->getKey(),
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $organization->getKey(),
        ]);
    }

    public function test_detach_related_organization_shows_error_notification_if_user_is_owner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('detach', $organization);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->danger()
                ->title('Delete failed')
                ->body(__('exceptions.api.can_not_remove_owner_from_organization'))
                ->persistent()
        );
        $this->assertDatabaseHas(Member::class, [
            'id' => $ownerMember->getKey(),
        ]);
    }

    public function test_detach_related_organization_shows_error_notification_if_user_still_has_time_entries(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        TimeEntry::factory()->forMember($member)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ])->callTableAction('detach', $organization);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->danger()
                ->title('Delete failed')
                ->body(__('exceptions.api.entity_still_in_use', [
                    'modelToDelete' => __('validation.entities.member'),
                    'modelInUse' => __('validation.entities.time_entry'),
                ]))
                ->persistent()
        );
        $this->assertDatabaseHas(Member::class, [
            'id' => $member->getKey(),
        ]);
    }

    public function test_related_owned_organizations_have_view_and_edit_actions_linking_to_organization_resource(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->withOwner($user)->create();
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(UserResource\RelationManagers\OwnedOrganizationsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => UserResource\Pages\EditUser::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertTableActionHasUrl('view', OrganizationResource::getUrl('view', [
            'record' => $organization->getKey(),
        ]), $organization);
        $response->assertTableActionHasUrl('edit', OrganizationResource::getUrl('edit', [
            'record' => $organization->getKey(),
        ]), $organization);
    }
}
