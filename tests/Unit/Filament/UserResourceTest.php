<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Filament\Resources\TimeEntryResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Service\DeletionService;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(TimeEntryResource::class)]
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
}
