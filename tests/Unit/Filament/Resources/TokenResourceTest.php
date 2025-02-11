<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\TokenResource;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(TokenResource::class)]
class TokenResourceTest extends FilamentTestCase
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

    public function test_can_list_tokens(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $tokens = Token::factory()->forClient($client)->createMany(5);

        // Act
        $response = Livewire::test(TokenResource\Pages\ListTokens::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($tokens);
    }

    public function test_list_tokens_with_filter_is_personal_access_client_true(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $personalAccessClient = Client::factory()->personalAccessClient()->create();
        $tokens = Token::factory()->forClient($client)->createMany(5);
        $personalAccessTokens = Token::factory()->forClient($personalAccessClient)->createMany(5);

        // Act
        $response = Livewire::test(TokenResource\Pages\ListTokens::class)
            ->filterTable('is_personal_access_client', true);

        // Assert
        $response->assertSuccessful();
        $response->assertCountTableRecords(5);
        $response->assertCanSeeTableRecords($personalAccessTokens);
        $response->assertCanNotSeeTableRecords($tokens);
    }

    public function test_list_tokens_with_filter_is_personal_access_client_false(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $personalAccessClient = Client::factory()->personalAccessClient()->create();
        $tokens = Token::factory()->forClient($client)->createMany(5);
        $personalAccessTokens = Token::factory()->forClient($personalAccessClient)->createMany(5);

        // Act
        $response = Livewire::test(TokenResource\Pages\ListTokens::class)
            ->filterTable('is_personal_access_client', false);

        // Assert
        $response->assertSuccessful();
        $response->assertCountTableRecords(5);
        $response->assertCanSeeTableRecords($tokens);
        $response->assertCanNotSeeTableRecords($personalAccessTokens);
    }

    public function test_can_see_view_page_of_token(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $token = Token::factory()->forClient($client)->create();

        // Act
        $response = Livewire::test(TokenResource\Pages\ViewToken::class, ['record' => $token->getKey()]);

        // Assert
        $response->assertSuccessful();
    }
}
