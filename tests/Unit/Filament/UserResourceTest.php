<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

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
}
