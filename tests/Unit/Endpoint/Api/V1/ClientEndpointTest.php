<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ClientController;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ClientController::class)]
class ClientEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $clients = Client::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_clients_of_organization_ordered_by_created_at_desc_per_default(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
            'clients:view:all',
        ]);
        $clients = Client::factory()->forOrganization($data->organization)->randomCreatedAt()->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
        $clients = Client::query()->orderBy('created_at', 'desc')->get();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 4)
            ->where('data.0.id', $clients->get(0)->getKey())
            ->where('data.1.id', $clients->get(1)->getKey())
            ->where('data.2.id', $clients->get(2)->getKey())
            ->where('data.3.id', $clients->get(3)->getKey())
        );
    }

    public function test_index_endpoint_returns_list_of_clients_assigned_to_employee_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
        ]);

        $clients = Client::factory()->forOrganization($data->organization)->createMany(2);
        $projectWithMembership1 = Project::factory()->forOrganization($data->organization)->forClient($clients->get(0))->addMember($data->member)->isPrivate()->create();
        $projectWithMembership2 = Project::factory()->forOrganization($data->organization)->forClient($clients->get(1))->addMember($data->member)->isPrivate()->create();

        $otherClients = Client::factory()->forOrganization($data->organization)->createMany(2);
        $projectWithoutMembership = Project::factory()->forOrganization($data->organization)->forClient($otherClients->get(0))->isPrivate()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 2)
            ->where('data.0.id', $clients->get(0)->getKey())
            ->where('data.1.id', $clients->get(1)->getKey())
        );
    }

    public function test_index_endpoint_without_filter_archived_returns_only_non_archived_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
            'clients:view:all',
        ]);
        $archivedClients = Client::factory()->forOrganization($data->organization)->archived()->createMany(2);
        $nonArchivedClients = Client::factory()->forOrganization($data->organization)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($nonArchivedClients->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_archived_true_returns_only_archived_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
            'clients:view:all',
        ]);
        $archivedClients = Client::factory()->forOrganization($data->organization)->archived()->createMany(2);
        $nonArchivedClients = Client::factory()->forOrganization($data->organization)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [
            $data->organization->getKey(),
            'archived' => 'true',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($archivedClients->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_archived_false_returns_only_non_archived_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
            'clients:view:all',
        ]);
        $archivedClients = Client::factory()->forOrganization($data->organization)->archived()->createMany(2);
        $nonArchivedClients = Client::factory()->forOrganization($data->organization)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [
            $data->organization->getKey(),
            'archived' => 'false',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($nonArchivedClients->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_archived_all_returns_all_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:view',
            'clients:view:all',
        ]);
        $archivedClients = Client::factory()->forOrganization($data->organization)->archived()->createMany(2);
        $nonArchivedClients = Client::factory()->forOrganization($data->organization)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.clients.index', [
            $data->organization->getKey(),
            'archived' => 'all',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
        $this->assertEqualsCanonicalizing($archivedClients->merge($nonArchivedClients)->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.clients.store', [$data->organization->getKey()]), [
            'name' => 'Test Client',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_client_with_same_name_already_exists(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:create',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.clients.store', [$data->organization->getKey()]), [
            'name' => $client->name,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A client with the same name already exists in the organization.',
        ]);
        $this->assertDatabaseCount(Client::class, 1);
    }

    public function test_store_endpoint_fails_if_client_with_same_name_exists_in_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:create',
        ]);
        $otherOrganization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.clients.store', [$data->organization->getKey()]), [
            'name' => $client->name,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseCount(Client::class, 2);
    }

    public function test_store_endpoint_creates_new_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:create',
        ]);
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.clients.store', [$data->organization->getKey()]), [
            'name' => $clientFake->name,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $clientFake->name)
        );
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_user_is_not_part_of_client_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $otherOrganization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($otherOrganization)->create();
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Client::class, [
            'id' => $client->getKey(),
            'name' => $client->name,
            'organization_id' => $otherOrganization->getKey(),
        ]);
    }

    public function test_update_endpoint_fails_if_client_if_client_with_same_name_already_exists(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $clientFake = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A client with the same name already exists in the organization.',
        ]);
        $this->assertDatabaseHas(Client::class, [
            'id' => $client->getKey(),
            'name' => $client->name,
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_update_endpoint_updates_client_name_even_if_client_with_same_name_exists_in_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $otherOrganization = Organization::factory()->create();
        $clientSameName = Client::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientSameName->name,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Client::class, [
            'id' => $client->getKey(),
            'name' => $clientSameName->name,
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_update_endpoint_updates_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $clientFake->name)
        );
        $this->assertDatabaseHas(Client::class, [
            'name' => $clientFake->name,
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_update_endpoint_can_archive_a_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
            'is_archived' => true,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.is_archived', true)
        );
        $client->refresh();
        $this->assertTrue($client->is_archived);
    }

    public function test_update_endpoint_can_unarchive_a_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:update',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->archived()->create();
        $clientFake = Client::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.clients.update', [$data->organization->getKey(), $client->getKey()]), [
            'name' => $clientFake->name,
            'is_archived' => false,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.is_archived', false)
        );
        $client->refresh();
        $this->assertFalse($client->is_archived);
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.clients.destroy', [$data->organization->getKey(), $client->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_user_is_not_part_of_client_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:delete',
        ]);
        $otherOrganization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.clients.destroy', [$data->organization->getKey(), $client->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Client::class, [
            'id' => $client->getKey(),
            'name' => $client->name,
            'organization_id' => $otherOrganization->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_client_is_still_in_use_by_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:delete',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.clients.destroy', [$data->organization->getKey(), $client->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The client is still used by a project and can not be deleted.');
        $this->assertDatabaseHas(Client::class, [
            'id' => $client->getKey(),
        ]);
    }

    public function test_destroy_endpoint_deletes_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'clients:delete',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.clients.destroy', [$data->organization->getKey(), $client->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(Client::class, [
            'id' => $client->getKey(),
        ]);
    }
}
