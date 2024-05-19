<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Models\Member;
use App\Models\Project;
use App\Models\Task;
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
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
        ]));

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
        $otherData = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'member_id' => $otherData->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('member_id');
    }

    public function test_index_endpoint_returns_time_entries_for_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
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
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create([
            'start' => Carbon::now()->subDay(),
        ]);
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create([
            'start' => Carbon::now()->subDays(2),
        ]);
        $timeEntry3 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create([
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
        $activeTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->active()->create();
        $nonActiveTimeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'active' => 'true',
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $activeTimeEntry->getKey());
    }

    public function test_index_endpoint_returns_only_non_active_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $activeTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->active()->createMany(3);
        $nonActiveTimeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'active' => 'false',
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $nonActiveTimeEntries->getKey());
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_less_time_entries_than_limit(): void
    {
        // Arrange

        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'member_id' => $data->member->getKey(),
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
        $timeEntriesDay1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(Carbon::now($data->user->timezone)->subDay()->startOfDay(), Carbon::now($data->user->timezone)->subDay()->endOfDay())
            ->createMany(3);
        $timeEntriesDay2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(Carbon::now($data->user->timezone)->subDays(2)->startOfDay(), Carbon::now($data->user->timezone)->subDays(2)->endOfDay())
            ->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_than_limit_with_a_timezone_edge_case(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $data->user->timezone = 'America/New_York';
        $data->user->save();
        /**
         * We create in the eyes of the users timezone 2 time entries yesterday, 5 time entries two days ago, and 3 time entries three days ago
         * The time entries are created in a way that they jump to the next day if the endpoint ignores the users timezone and just uses UTC
         */

        // Note: This entry is yesterday in user timezone and yesterday in UTC
        $timeEntriesDay1InUserTimeZone = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now($data->user->timezone)->subDay()->startOfDay()->utc(),
            ])
            ->createMany(2);
        //dump($timeEntriesDay1InUserTimeZone->first()->refresh()->start->toImmutable()->timezone('UTC')->toDateString());
        //dump($timeEntriesDay1InUserTimeZone->first()->refresh()->start->toImmutable()->timezone($data->user->timezone)->toDateString());
        // Note: This entry is yesterday in UTC timezone, but two days ago in user timezone
        $timeEntriesDay1InUTC = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now('UTC')->subDay()->startOfDay()->utc(),
            ])
            ->createMany(2);
        //dump($timeEntriesDay1InUTC->first()->refresh()->start->toImmutable()->timezone('UTC')->toDateString());
        //dump($timeEntriesDay1InUTC->first()->refresh()->start->toImmutable()->timezone($data->user->timezone)->toDateString());
        // Note: This entry is two days ago in user timezone
        $timeEntriesDay2InUserTimeZone = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now($data->user->timezone)->subDays(2)->startOfDay()->utc(),
            ])
            ->createMany(3);

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_in_latest_day_than_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesDay1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(Carbon::now()->subDay()->startOfDay(), Carbon::now()->subDay()->endOfDay())
            ->createMany(7);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'only_full_dates' => 'true',
            'limit' => 5,
            'member_id' => $data->member->getKey(),
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
        $timeEntriesAfter = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(
                Carbon::now()->timezone($data->user->timezone)->subDay()->startOfDay()->utc(),
                Carbon::now()->timezone($data->user->timezone)->utc()
            )
            ->createMany(3);
        $timeEntriesBefore = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(
                Carbon::now()->timezone($data->user->timezone)->subDays(2)->startOfDay()->utc(),
                Carbon::now()->timezone($data->user->timezone)->subDays(2)->endOfDay()->utc()
            )
            ->createMany(3);
        $timeEntriesDirectlyBeforeLimit = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->create([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDays(2)->endOfDay()->utc(),
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'before' => Carbon::now()->timezone($data->user->timezone)->subDay()->startOfDay()->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
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
        $timeEntriesAfter = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(Carbon::now($data->user->timezone)->startOfDay()->utc(), Carbon::now($data->user->timezone)->utc())
            ->createMany(3);
        $timeEntriesBefore = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->startBetween(Carbon::now($data->user->timezone)->subDay()->startOfDay()->utc(), Carbon::now($data->user->timezone)->subDay()->endOfDay()->utc())
            ->createMany(3);
        $timeEntriesDirectlyAfterLimit = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->create([
                'start' => Carbon::now($data->user->timezone)->startOfDay()->utc(),
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'after' => Carbon::now($data->user->timezone)->subDay()->endOfDay()->toIso8601ZuluString(), // yesterday
            'member_id' => $data->member->getKey(),
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

    public function test_aggregate_endpoint_fails_if_user_has_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_aggregate_endpoint_groups_by_two_groups(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->createMany(3);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->state([
            'start' => $timeEntries->get(0)->start,
        ])->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'day',
            'sub_group' => 'project',
        ]));

        // Assert
        $response->assertSuccessful();
    }

    public function test_aggregate_endpoint_groups_by_one_group(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->createMany(3);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->state([
            'start' => $timeEntries->get(0)->start,
        ])->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'week',
        ]));

        // Assert
        $response->assertSuccessful();
    }

    public function test_aggregate_endpoint_with_no_group(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->createMany(3);
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->state([
            'start' => $timeEntries->get(0)->start,
        ])->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
        ]));

        // Assert
        $response->assertSuccessful();
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
            'member_id' => $data->member->getKey(),
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
        $activeTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->active()->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->withTags($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => null,
            'tags' => $timeEntryFake->tags,
            'member_id' => $data->member->getKey(),
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
            'member_id' => $data->member->getKey(),
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake2->task_id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'task_id' => 'The task is not part of the given project.',
        ]);
    }

    public function test_store_endpoint_validation_fails_if_project_id_is_missing_but_request_has_task_id(): void
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
            'member_id' => $data->member->getKey(),
            'task_id' => $timeEntryFake2->task_id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_id' => 'The project field is required when task is present.',
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
            'member_id' => $data->member->getKey(),
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'member_id' => $data->member->getKey(),
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
            'member_id' => $data->member->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'member_id' => $data->member->getKey(),
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
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'member_id' => $otherMember->getKey(),
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
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'member_id' => $otherMember->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'user_id' => $otherUser->getKey(),
            'member_id' => $otherMember->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_own_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'member_id' => $data->member->getKey(),
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
        $timeEntry = TimeEntry::factory()->forOrganization($otherUser->organization)->forMember($otherUser->member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
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
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_validation_fails_if_task_id_does_not_belong_to_project_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        $timeEntryFake2 = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'project_id' => $timeEntryFake->project_id,
            'task_id' => $timeEntryFake2->task_id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'task_id' => 'The task is not part of the given project.',
        ]);
    }

    public function test_update_endpoint_validation_fails_if_project_id_is_missing_but_request_has_task_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        $timeEntryFake2 = TimeEntry::factory()->forOrganization($data->organization)->withTask($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'task_id' => $timeEntryFake2->task_id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'project_id' => 'The project field is required when task is present.',
            'task_id' => 'The task is not part of the given project.',
        ]);
    }

    public function test_update_endpoint_updates_time_entry_for_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->withTags($data->organization)->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'member_id' => $data->member->getKey(),
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $data->member->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoint_fails_if_user_tries_to_reactivate_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => null,
            'tags' => $timeEntryFake->tags,
            'member_id' => $data->member->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('error', true);
        $response->assertJsonPath('message', __('exceptions.api.'.TimeEntryCanNotBeRestartedApiException::KEY));
    }

    public function test_update_endpoint_updates_time_entry_of_other_user_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'description' => $timeEntryFake->description,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'tags' => $timeEntryFake->tags,
            'member_id' => $member->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $member->getKey(),
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
        $timeEntry = TimeEntry::factory()->forOrganization($otherUser->organization)->forMember($otherUser->member)->create();
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
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
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
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
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
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
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
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
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

    public function test_update_multiple_endpoint_fails_if_user_has_no_permission_to_update_own_time_entries_or_all_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        $timeEntriesFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => $timeEntries->pluck('id')->toArray(),
            'changes' => [
                'description' => $timeEntriesFake->description,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertForbidden();
    }

    public function test_update_multiple_updates_own_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_own_time_entries_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $timeEntriesFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
            'changes' => [
                'description' => $timeEntriesFake->description,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => [
                $ownTimeEntry->getKey(),
            ],
            'error' => [
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
            'description' => $timeEntriesFake->description,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
            'description' => $otherOrganizationTimeEntry->description,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
            'description' => $otherTimeEntry->description,
        ]);
    }

    public function test_update_multiple_updates_own_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_own_time_entries_permission_and_full_changeset(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $timeEntriesFake = TimeEntry::factory()->forOrganization($data->organization)->withTags($data->organization)->make();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forProject($project)->forOrganization($data->organization)->create();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
            'changes' => [
                'member_id' => $data->member->getKey(),
                'project_id' => $project->getKey(),
                'task_id' => $task->getKey(),
                'billable' => $timeEntriesFake->billable,
                'description' => $timeEntriesFake->description,
                'tags' => $timeEntriesFake->tags,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => [
                $ownTimeEntry->getKey(),
            ],
            'error' => [
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
            'member_id' => $data->member->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
            'billable' => $timeEntriesFake->billable,
            'description' => $timeEntriesFake->description,
            'tags' => json_encode($timeEntriesFake->tags),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
            'member_id' => $otherOrganizationTimeEntry->member_id,
            'project_id' => $otherOrganizationTimeEntry->project_id,
            'task_id' => $otherOrganizationTimeEntry->task_id,
            'billable' => $otherOrganizationTimeEntry->billable,
            'description' => $otherOrganizationTimeEntry->description,
            'tags' => json_encode($otherOrganizationTimeEntry->tags),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
            'member_id' => $otherTimeEntry->member_id,
            'project_id' => $otherTimeEntry->project_id,
            'task_id' => $otherTimeEntry->task_id,
            'billable' => $otherTimeEntry->billable,
            'description' => $otherTimeEntry->description,
            'tags' => json_encode($otherTimeEntry->tags),
        ]);
    }

    public function test_update_multiple_updates_all_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_all_time_entries_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $timeEntriesFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
            'changes' => [
                'description' => $timeEntriesFake->description,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
            ],
            'error' => [
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
            'description' => $timeEntriesFake->description,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
            'description' => $otherOrganizationTimeEntry->description,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
            'description' => $timeEntriesFake->description,
        ]);
    }

    public function test_update_multiple_updates_all_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_all_time_entries_permission_and_full_changeset(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $timeEntriesFake = TimeEntry::factory()->forOrganization($data->organization)->withTags($data->organization)->make();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forProject($project)->forOrganization($data->organization)->create();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
            'changes' => [
                'member_id' => $otherMember->getKey(),
                'project_id' => $project->getKey(),
                'task_id' => $task->getKey(),
                'billable' => $timeEntriesFake->billable,
                'description' => $timeEntriesFake->description,
                'tags' => $timeEntriesFake->tags,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
            ],
            'error' => [
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
            'member_id' => $otherMember->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
            'billable' => $timeEntriesFake->billable,
            'description' => $timeEntriesFake->description,
            'tags' => json_encode($timeEntriesFake->tags),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
            'member_id' => $otherOrganizationTimeEntry->member_id,
            'project_id' => $otherOrganizationTimeEntry->project_id,
            'task_id' => $otherOrganizationTimeEntry->task_id,
            'billable' => $otherOrganizationTimeEntry->billable,
            'description' => $otherOrganizationTimeEntry->description,
            'tags' => json_encode($otherOrganizationTimeEntry->tags),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
            'member_id' => $otherMember->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
            'billable' => $timeEntriesFake->billable,
            'description' => $timeEntriesFake->description,
            'tags' => json_encode($timeEntriesFake->tags),
        ]);
    }

    public function test_update_multiple_updates_own_time_entries_fails_if_member_id_is_not_your_own_and_you_dont_have_update_all_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
            ],
            'changes' => [
                'member_id' => $otherMember->getKey(),
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(403);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
            'member_id' => $ownTimeEntry->member_id,
        ]);
    }
}
