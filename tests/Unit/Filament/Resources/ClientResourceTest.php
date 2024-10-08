<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ClientResource::class)]
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

    public function test_can_see_edit_page_of_client(): void
    {
        // Arrange
        $client = Client::factory()->create();

        // Act
        $response = Livewire::test(ClientResource\Pages\EditClient::class, ['record' => $client->getKey()]);

        // Assert
        $response->assertSuccessful();
    }
}
