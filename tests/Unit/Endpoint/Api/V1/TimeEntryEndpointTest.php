<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use TiMacDonald\Log\LogEntry;

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
        $response->assertForbidden();
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
        $response->assertForbidden();
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

    public function test_index_endpoint_returns_time_entries_for_all_users_in_organization_default_sort_by_start_date_desc(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $user = User::factory()->create();
        $data->organization->users()->attach($user, [
            'role' => 'employee',
        ]);
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create([
            'start' => Carbon::now()->subDay(),
        ]);
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forUser($user)->create([
            'start' => Carbon::now()->subDays(2),
        ]);
        $timeEntry3 = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->create([
            'start' => Carbon::now()->subDays(3),
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $timeEntry1->getKey());
        $response->assertJsonPath('data.1.id', $timeEntry2->getKey());
        $response->assertJsonPath('data.2.id', $timeEntry3->getKey());
    }

    public function test_index_endpoint_returns_only_active_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $activeTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->active()->create();
        $nonActiveTimeEntries = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'active' => 'true',
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $activeTimeEntry->getKey());
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_less_time_entries_than_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_than_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesDay1 = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDay()->startOfDay(), Carbon::now()->subDay()->endOfDay())
            ->createMany(3);
        $timeEntriesDay2 = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDays(2)->startOfDay(), Carbon::now()->subDays(2)->endOfDay())
            ->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_in_latest_day_than_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesDay1 = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDay()->startOfDay(), Carbon::now()->subDay()->endOfDay())
            ->createMany(7);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        Log::assertLogged(fn (LogEntry $log) => $log->level === 'warning'
            && $log->message === 'User has has more than 5 time entries on one date'
        );
        $response->assertStatus(200);
        $response->assertJsonCount(7, 'data');
    }

    public function test_index_endpoint_before_filter_returns_time_entries_before_date(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesAfter = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDay()->startOfDay(), Carbon::now())
            ->createMany(3);
        $timeEntriesBefore = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDays(2)->startOfDay(), Carbon::now()->subDays(2)->endOfDay())
            ->createMany(3);
        $timeEntriesDirectlyBeforeLimit = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->create([
                'start' => Carbon::now()->subDays(2)->endOfDay(),
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'before' => Carbon::now()->subDay()->startOfDay()->toIso8601ZuluString(),
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->count('data', 4)
            ->where('data.0.id', $timeEntriesDirectlyBeforeLimit->getKey())
            ->where('data.1.id', $timeEntriesBefore->sortByDesc('start')->get(0)->getKey())
            ->where('data.2.id', $timeEntriesBefore->sortByDesc('start')->get(1)->getKey())
            ->where('data.3.id', $timeEntriesBefore->sortByDesc('start')->get(2)->getKey())
        );
    }

    public function test_index_endpoint_after_filter_returns_time_entries_after_date(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesAfter = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->startOfDay(), Carbon::now())
            ->createMany(3);
        $timeEntriesBefore = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->startBetween(Carbon::now()->subDay()->startOfDay(), Carbon::now()->subDay()->endOfDay())
            ->createMany(3);
        $timeEntriesDirectlyAfterLimit = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)
            ->create([
                'start' => Carbon::now()->startOfDay(),
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'after' => Carbon::now()->subDay()->endOfDay()->toIso8601ZuluString(), // yesterday
            'user_id' => $data->user->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->count('data', 4)
            ->where('data.0.id', $timeEntriesAfter->sortByDesc('start')->get(0)->getKey())
            ->where('data.1.id', $timeEntriesAfter->sortByDesc('start')->get(1)->getKey())
            ->where('data.2.id', $timeEntriesAfter->sortByDesc('start')->get(2)->getKey())
            ->where('data.3.id', $timeEntriesDirectlyAfterLimit->getKey())
        );
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
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_user_already_has_active_time_entry_and_tries_to_start_new_one(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $activeTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forUser($data->user)->active()->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->withTags($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => null,
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('error', true);
    }

    public function test_store_endpoint_validation_fails_if_task_id_does_not_belong_to_project_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        $timeEntryFake2 = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake2->task_id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'task_id' => 'The task is not part of the given project.',
        ]);
    }

    public function test_store_endpoint_creates_new_time_entry_for_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->withTask($data->organization)->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'project_id' => $timeEntryFake->project_id,
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

    public function test_store_endpoint_creates_new_time_entry_with_minimal_fields(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'user_id' => $data->user->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'user_id' => $data->user->getKey(),
            'task_id' => null,
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
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $otherUser->getKey(),
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
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
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
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
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
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
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $data->user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
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
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'user_id' => $user->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
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
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
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
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
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
        $response->assertForbidden();
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
        $response->assertForbidden();
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
        $response->assertForbidden();
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
        $response->assertNoContent();
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
        $response->assertNoContent();
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
    }
}
