<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\TagController;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\TimeEntry;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(TagController::class)]
class TagEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_tags(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $tags = Tag::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tags.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_tags_of_organization_ordered_by_created_at_desc_per_default(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:view',
        ]);
        $tags = Tag::factory()->forOrganization($data->organization)->randomCreatedAt()->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tags.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
        $tags = Tag::query()->orderBy('created_at', 'desc')->get();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 4)
            ->where('data.0.id', $tags->get(0)->getKey())
            ->where('data.1.id', $tags->get(1)->getKey())
            ->where('data.2.id', $tags->get(2)->getKey())
            ->where('data.3.id', $tags->get(3)->getKey())
        );
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_tags(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tags.store', [$data->organization->getKey()]), [
            'name' => 'Test Tag',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_new_tag(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:create',
        ]);
        $tagFake = Tag::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tags.store', [$data->organization->getKey()]), [
            'name' => $tagFake->name,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $tagFake->name)
        );
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_tags(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        $tagFake = Tag::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tags.update', [$data->organization->getKey(), $tag->getKey()]), [
            'name' => $tagFake->name,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_user_is_not_part_of_tag_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:update',
        ]);
        $otherOrganization = Organization::factory()->create();
        $tag = Tag::factory()->forOrganization($otherOrganization)->create();
        $tagFake = Tag::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tags.update', [$data->organization->getKey(), $tag->getKey()]), [
            'name' => $tagFake->name,
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Tag::class, [
            'id' => $tag->getKey(),
            'name' => $tag->name,
            'organization_id' => $otherOrganization->getKey(),
        ]);
    }

    public function test_update_endpoint_updates_tag(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:update',
        ]);
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        $tagFake = Tag::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tags.update', [$data->organization->getKey(), $tag->getKey()]), [
            'name' => $tagFake->name,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $tagFake->name)
        );
        $this->assertDatabaseHas(Tag::class, [
            'name' => $tagFake->name,
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_tags(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tags.destroy', [$data->organization->getKey(), $tag->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_user_is_not_part_of_tag_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:delete',
        ]);
        $otherOrganization = Organization::factory()->create();
        $tag = Tag::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tags.destroy', [$data->organization->getKey(), $tag->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Tag::class, [
            'id' => $tag->getKey(),
            'name' => $tag->name,
            'organization_id' => $otherOrganization->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_tag_is_still_in_use_by_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:delete',
        ]);
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create([
            'tags' => [$tag->getKey()],
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tags.destroy', [$data->organization->getKey(), $tag->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The tag is still used by a time entry and can not be deleted.');
        $this->assertDatabaseHas(Tag::class, [
            'id' => $tag->getKey(),
        ]);
    }

    public function test_destroy_endpoint_deletes_tag(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tags:delete',
        ]);
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tags.destroy', [$data->organization->getKey(), $tag->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(Tag::class, [
            'id' => $tag->getKey(),
        ]);
    }
}
