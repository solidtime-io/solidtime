<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class ClientResourceTest extends FilamentTestCase
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

    public function test_can_list_clients(): void
    {
        // Arrange
        $clients = Client::factory()->createMany(5);

        // Act
        $response = Livewire::test(ClientResource\Pages\ListClients::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($clients);
    }
}
