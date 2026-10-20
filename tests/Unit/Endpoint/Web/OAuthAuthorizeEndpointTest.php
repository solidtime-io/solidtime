<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Models\Passport\Client;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AuthServiceProvider::class)]
class OAuthAuthorizeEndpointTest extends EndpointTestAbstract
{
    public function test_authorize_redirects_guest_to_login_and_returns_to_authorize_after_login(): void
    {
        // Arrange
        $client = $this->createPublicClient();

        // Act
        $response = $this->get($this->authorizeUrl($client));

        // Assert
        $response->assertRedirect('/login');
        $intended = (string) session('url.intended');
        $this->assertSame(route('passport.authorizations.authorize'), strtok($intended, '?'));
        parse_str((string) parse_url($intended, PHP_URL_QUERY), $query);
        $this->assertSame($client->getKey(), $query['client_id']);
        $this->assertSame('test-state', $query['state']);
    }

    public function test_authorize_returns_json_401_for_guest_that_expects_json(): void
    {
        // Arrange
        $client = $this->createPublicClient();

        // Act
        $response = $this->getJson($this->authorizeUrl($client));

        // Assert
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_authorize_shows_authorization_prompt_for_logged_in_user(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $client = $this->createPublicClient();
        $this->withoutVite();
        $this->actingAs($user);

        // Act
        $response = $this->get($this->authorizeUrl($client));

        // Assert
        $response->assertOk();
        $response->assertViewIs('auth.oauth.authorize');
    }

    private function createPublicClient(): Client
    {
        $clientRepository = new ClientRepository;
        /** @var Client $client */
        $client = $clientRepository->createAuthorizationCodeGrantClient(
            name: 'Mobile App',
            redirectUris: ['solidtime://oauth/callback'],
            confidential: false
        );

        return $client;
    }

    private function authorizeUrl(Client $client): string
    {
        return route('passport.authorizations.authorize', [
            'client_id' => $client->getKey(),
            'redirect_uri' => 'solidtime://oauth/callback',
            'response_type' => 'code',
            'scope' => '*',
            'state' => 'test-state',
            'code_challenge' => 'OIgPM3ofpRyYSVuOyQvU2TL3geEW7ignRVJCxeeLzj4',
            'code_challenge_method' => 'S256',
        ]);
    }
}
