<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Enums\Role;
use App\Events\OrganizationInvitationAdding;
use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\UserResource;
use App\Mail\OrganizationInvitationMail;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DeletionService;
use App\Service\Export\ExportService;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use App\Service\Import\ImportService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_can_delete_an_organization_from_the_table(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $this->mock(DeletionService::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('deleteOrganization')
                ->withArgs(fn (Organization $organizationArg) => $organizationArg->is($organization))
                ->once();
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('delete', $organization);

        // Assert
        $response->assertHasNoTableActionErrors();
        $response->assertSuccessful();
    }

    public function test_can_export_an_organization(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        Storage::disk(config('filesystems.private'))->put('exports/export.zip', 'zip-content');
        $organization = Organization::factory()->create();
        $this->mock(ExportService::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('export')
                ->withArgs(fn (Organization $organizationArg) => $organizationArg->is($organization))
                ->once()
                ->andReturn('exports/export.zip');
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Export', $organization);

        // Assert
        $response->assertHasNoTableActionErrors();
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Export successful')
                ->success()
                ->persistent()
        );
        $response->assertFileDownloaded('export.zip');
    }

    public function test_export_organization_shows_error_notification_on_failure(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $this->mock(ExportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('export')
                ->once()
                ->andThrow(new Exception('Something went wrong'));
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Export', $organization);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Export failed')
                ->danger()
                ->body('Message: Something went wrong')
                ->persistent()
        );
        $response->assertNoFileDownloaded();
    }

    public function test_can_import_into_an_organization(): void
    {
        // Arrange
        Storage::fake(config('filament.default_filesystem_disk'));
        Storage::disk(config('filament.default_filesystem_disk'))->put('imports/import.csv', 'csv-content');
        $organization = Organization::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('import')
                ->withArgs(function (Organization $organizationArg, string $importerType, string $data, string $timezone) use ($organization): bool {
                    return $organizationArg->is($organization)
                        && $importerType === 'toggl_time_entries'
                        && $data === 'csv-content'
                        && $timezone === 'Europe/Vienna';
                })
                ->once()
                ->andReturn(new ReportDto(
                    clientsCreated: 1,
                    projectsCreated: 2,
                    tasksCreated: 3,
                    timeEntriesCreated: 4,
                    tagsCreated: 5,
                    usersCreated: 6,
                ));
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Import', $organization, data: [
                'file' => [(string) Str::uuid() => 'imports/import.csv'],
                'type' => 'toggl_time_entries',
                'timezone' => 'Europe/Vienna',
            ]);

        // Assert
        $response->assertHasNoTableActionErrors();
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Import successful')
                ->success()
                ->body(
                    'Imported time entries: 4<br>'.
                    'Imported clients: 1<br>'.
                    'Imported projects: 2<br>'.
                    'Imported tasks: 3<br>'.
                    'Imported tags: 5<br>'.
                    'Imported users: 6'
                )
                ->persistent()
        );
    }

    public function test_import_into_organization_shows_error_notification_on_import_exception(): void
    {
        // Arrange
        Storage::fake(config('filament.default_filesystem_disk'));
        Storage::disk(config('filament.default_filesystem_disk'))->put('imports/import.csv', 'csv-content');
        $organization = Organization::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('import')
                ->once()
                ->andThrow(new ImportException('Invalid CSV'));
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Import', $organization, data: [
                'file' => [(string) Str::uuid() => 'imports/import.csv'],
                'type' => 'toggl_time_entries',
                'timezone' => 'Europe/Vienna',
            ]);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Import failed, changes rolled back')
                ->danger()
                ->body('Message: Invalid CSV')
                ->persistent()
        );
    }

    public function test_import_into_organization_shows_error_notification_if_file_is_missing(): void
    {
        // Arrange
        Storage::fake(config('filament.default_filesystem_disk'));
        $organization = Organization::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('import');
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Import', $organization, data: [
                'file' => [(string) Str::uuid() => 'imports/does-not-exist.csv'],
                'type' => 'toggl_time_entries',
                'timezone' => 'Europe/Vienna',
            ]);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Import failed, changes rolled back')
                ->danger()
                ->body('Message: File not found')
                ->persistent()
        );
    }

    public function test_import_into_organization_shows_error_notification_if_file_is_missing_on_non_throwing_disk(): void
    {
        // Arrange
        Storage::fake(config('filament.default_filesystem_disk'), ['throw' => false]);
        $organization = Organization::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('import');
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Import', $organization, data: [
                'file' => [(string) Str::uuid() => 'imports/does-not-exist.csv'],
                'type' => 'toggl_time_entries',
                'timezone' => 'Europe/Vienna',
            ]);

        // Assert
        $response->assertSuccessful();
        $response->assertNotified(
            Notification::make()
                ->title('Import failed, changes rolled back')
                ->danger()
                ->body('Message: File not found')
                ->persistent()
        );
    }

    public function test_import_into_organization_requires_file_type_and_timezone(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('import');
        });

        // Act
        $response = Livewire::test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableAction('Import', $organization, data: [
                'file' => null,
                'type' => null,
                'timezone' => null,
            ]);

        // Assert
        $response->assertHasTableActionErrors(['file', 'type', 'timezone']);
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

    public function test_can_create_related_invitation(): void
    {
        // Arrange
        Event::fake([
            OrganizationInvitationAdding::class,
        ]);
        Mail::fake();
        $organization = Organization::factory()->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\InvitationsRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('create', data: [
            'email' => 'new-user@example.com',
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertDatabaseHas(OrganizationInvitation::class, [
            'organization_id' => $organization->getKey(),
            'email' => 'new-user@example.com',
            'role' => Role::Employee->value,
        ]);
        Event::assertDispatched(OrganizationInvitationAdding::class);
        Mail::assertQueued(OrganizationInvitationMail::class);
    }

    public function test_related_users_have_view_action_linking_to_user_resource(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertTableActionHasUrl('view', UserResource::getUrl('view', [
            'record' => $user->getKey(),
        ]), $user);
    }

    public function test_can_attach_user_to_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('attach', data: [
            'recordId' => $user->getKey(),
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertDatabaseHas(Member::class, [
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Employee->value,
        ]);
    }

    public function test_attach_user_to_organization_fails_with_owner_or_placeholder_role(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        foreach ([Role::Owner, Role::Placeholder] as $role) {
            // Act
            $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
                'ownerRecord' => $organization,
                'pageClass' => OrganizationResource\Pages\EditOrganization::class,
            ])->callTableAction('attach', data: [
                'recordId' => $user->getKey(),
                'role' => $role->value,
            ]);

            // Assert
            $response->assertHasTableActionErrors(['role']);
        }
        $this->assertDatabaseMissing(Member::class, [
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_can_edit_role_and_billable_rate_of_related_user(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->billableRate(1000)->create();
        $billableTimeEntry = TimeEntry::factory()->forMember($member)->billableRate(1000)->create();
        $nonBillableTimeEntry = TimeEntry::factory()->forMember($member)->notBillable()->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('edit', $user, data: [
            'role' => Role::Admin->value,
            'billable_rate' => 2000,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $member->refresh();
        $this->assertSame(Role::Admin->value, $member->role);
        $this->assertSame(2000, $member->billable_rate);
        $this->assertSame(2000, $billableTimeEntry->refresh()->billable_rate);
        $this->assertNull($nonBillableTimeEntry->refresh()->billable_rate);
    }

    public function test_can_make_related_user_owner_of_organization(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('edit', $user, data: [
            'role' => Role::Owner->value,
            'billable_rate' => null,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertSame(Role::Owner->value, $member->refresh()->role);
        $this->assertSame(Role::Admin->value, $ownerMember->refresh()->role);
        $this->assertSame($user->getKey(), $organization->refresh()->user_id);
    }

    public function test_edit_related_user_shows_error_notification_if_role_of_owner_is_changed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('edit', $owner, data: [
            'role' => Role::Admin->value,
            'billable_rate' => null,
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

    public function test_edit_related_user_does_not_change_role_if_role_is_unchanged_for_owner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->billableRate(1000)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('edit', $owner, data: [
            'role' => Role::Owner->value,
            'billable_rate' => 2000,
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
        $ownerMember->refresh();
        $this->assertSame(Role::Owner->value, $ownerMember->role);
        $this->assertSame(2000, $ownerMember->billable_rate);
    }

    public function test_edit_related_user_does_not_change_role_if_role_is_unchanged_for_placeholder(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->placeholder()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Placeholder)->billableRate(1000)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('edit', $user, data: [
            'role' => Role::Placeholder->value,
            'billable_rate' => 2000,
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
        $member->refresh();
        $this->assertSame(Role::Placeholder->value, $member->role);
        $this->assertSame(2000, $member->billable_rate);
    }

    public function test_can_detach_related_user_from_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('detach', $user);

        // Assert
        $response->assertSuccessful();
        $response->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing(Member::class, [
            'id' => $member->getKey(),
        ]);
        $this->assertDatabaseHas(User::class, [
            'id' => $user->getKey(),
        ]);
    }

    public function test_detach_related_user_shows_error_notification_if_user_is_owner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $ownerMember = Member::factory()->forOrganization($organization)->forUser($owner)->role(Role::Owner)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('detach', $owner);

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

    public function test_detach_related_user_shows_error_notification_if_user_still_has_time_entries(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        TimeEntry::factory()->forMember($member)->create();

        // Act
        $response = Livewire::test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => OrganizationResource\Pages\EditOrganization::class,
        ])->callTableAction('detach', $user);

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
}
