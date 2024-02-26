<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

class TimeEntryEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_time_entries_for_others_but_wants_all_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_index_endpoint_returns_time_entries_for_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey(), 'user_id' => $data->user->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $timeEntry->getKey());
    }

    public function test_index_endpoint_fails_if_user_filter_is_from_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $user = User::factory()->withPersonalOrganization()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey(), 'user_id' => $user->getKey()]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('user_id');
    }

    public function test_index_endpoint_returns_time_entries_for_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey(), 'user_id' => $user->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $timeEntry->getKey());
    }

    public function test_index_endpoint_returns_time_entries_for_all_users_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $timeEntry->getKey());
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTags($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_store_endpoint_creates_new_time_entry_for_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_time_entries_for_others(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $otherUser = User::factory()->create();
        $data->organization->users()->attach($otherUser, [
            'role' => 'employee',
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $otherUser->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_store_endpoint_creates_new_time_entry_for_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:all',
        ]);
        $otherUser = User::factory()->create();
        $data->organization->users()->attach($otherUser, [
            'role' => 'employee',
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $otherUser->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'user_id' => $otherUser->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_own_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_update_endpoint_fails_if_user_is_not_part_of_time_entry_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $otherUser = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($otherUser->organization)->forUser($otherUser->user)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_time_entries_for_other_users_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_update_endpoint_updates_time_entry_for_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoint_updates_time_entry_of_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601String(),
            'end' => $timeEntryFake->end->toIso8601String(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'user_id' => $user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_tries_to_delete_time_entry_in_organization_that_they_does_belong_to(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:all',
        ]);
        $otherUser = $this->createUserWithPermission([
            'time-entries:delete:all',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($otherUser->organization)->forUser($otherUser->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_endpoint_fails_if_user_tries_to_delete_non_existing_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), Str::uuid()]));

        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_own_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_time_entries_for_other_users_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_endpoint_deletes_own_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
    }

    public function test_destroy_endpoint_deletes_time_entry_of_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:all',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
    }
}
