<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\ExportFormat;
use App\Enums\Role;
use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\Client;
use App\Models\Member;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;
use Ramsey\Uuid\Type\Time;
use TiMacDonald\Log\LogEntry;

#[UsesClass(TimeEntryController::class)]
class TimeEntryEndpointTest extends ApiEndpointTestAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
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
        $this->assertResponseCode($response, 200);
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
        $this->assertResponseCode($response, 200);
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
        $this->assertResponseCode($response, 200);
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 1);
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 1);
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('meta.total', 3);
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('meta.total', 6);
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_than_limit_with_a_timezone_edge_case(): void
    {
        // Arrange
        $now = Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna');
        $this->freezeTime($now);
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $data->user->timezone = 'America/New_York';
        $data->user->save();
        /**
         * We create in the eyes of the users timezone 2 time entries yesterday, 2 time entries two days ago, and 3 time entries three days ago
         * The time entries are created in a way that they jump to the next day if the endpoint ignores the users timezone and just uses UTC
         */

        // Note: This entry is yesterday in user timezone and yesterday in UTC
        $timeEntriesDay1InUserTimeZone = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDay()->startOfDay()->utc(),
            ])
            ->createMany(2);
        // Note: This entry is yesterday in UTC timezone, but two days ago in user timezone
        $timeEntriesDay1InUTC = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now()->utc()->subDay()->startOfDay()->utc(),
            ])
            ->createMany(2);
        // Note: This entry is two days ago in user timezone
        $timeEntriesDay2InUserTimeZone = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDays(2)->startOfDay()->utc(),
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 7);
    }

    public function test_index_endpoint_filter_only_full_dates_returns_time_entries_for_the_whole_day_case_more_time_entries_in_latest_day_than_limit(): void
    {
        // Arrange
        $now = Carbon::create(2024, 1, 1, 12, 0, 0, 'Europe/Vienna');
        $this->freezeTime($now);
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $timeEntriesDay1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDay()->startOfDay()->utc(),
            ])
            ->createMany(7);
        $timeEntriesDay2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->state([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDays(2)->endOfDay()->utc(),
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
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(7, 'data');
        $response->assertJsonPath('meta.total', 10);
        Log::assertLogged(fn (LogEntry $log) => $log->level === 'warning'
            && $log->message === 'User has has more than 5 time entries on one date'
        );
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
        $timeEntriesBeforeSorted = $timeEntriesBefore->sortByDesc('start')->values();
        $timeEntriesDirectlyBeforeLimit = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)
            ->create([
                'start' => Carbon::now()->timezone($data->user->timezone)->subDays(2)->endOfDay()->utc(),
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'end' => Carbon::now()->timezone($data->user->timezone)->subDay()->startOfDay()->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('meta')
            ->where('meta.total', 4)
            ->count('data', 4)
            ->where('data.0.id', $timeEntriesDirectlyBeforeLimit->getKey())
            ->where('data.1.id', $timeEntriesBeforeSorted->get(0)->getKey())
            ->where('data.2.id', $timeEntriesBeforeSorted->get(1)->getKey())
            ->where('data.3.id', $timeEntriesBeforeSorted->get(2)->getKey())
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
        $timeEntriesAfterSorted = $timeEntriesAfter->sortByDesc('start')->values();
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
            'start' => Carbon::now($data->user->timezone)->subDay()->endOfDay()->toIso8601ZuluString(), // yesterday
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('meta')
            ->where('meta.total', 4)
            ->count('data', 4)
            ->where('data.0.id', $timeEntriesAfterSorted->get(0)->getKey())
            ->where('data.1.id', $timeEntriesAfterSorted->get(1)->getKey())
            ->where('data.2.id', $timeEntriesAfterSorted->get(2)->getKey())
            ->where('data.3.id', $timeEntriesDirectlyAfterLimit->getKey())
        );
    }

    public function test_index_endpoint_with_all_available_filters(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
            'time-entries:view:own',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create();
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        $timeEntry1 = TimeEntry::factory()
            ->forOrganization($data->organization)
            ->forProject($project)
            ->forTask($task)
            ->forMember($data->member)
            ->billable()
            ->active()
            ->create([
                'start' => Carbon::now()->subHour(),
                'tags' => [$tag->getKey()],
            ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
            'member_ids' => [$data->member->getKey()],
            'project_ids' => [$project->getKey()],
            'task_ids' => [$task->getKey()],
            'tag_ids' => [$tag->getKey()],
            'start' => Carbon::now()->subDay()->toIso8601ZuluString(),
            'end' => Carbon::now()->toIso8601ZuluString(),
            'active' => 'true',
            'only_full_dates' => 'true',
            'limit' => 1,
        ]));

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('meta')
            ->where('meta.total', 1)
            ->count('data', 1)
            ->where('data.0.id', $timeEntry1->getKey())
        );
    }

    public function test_index_endpoint_with_limit_offset_and_only_full_dates_deactivated(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $project1 = Project::factory()->forOrganization($data->organization)->create();
        $project2 = Project::factory()->forOrganization($data->organization)->create();
        TimeEntry::factory()->forMember($data->member)->forProject($project1)->forOrganization($data->organization)->create([
            'start' => Carbon::now()->subDays(2),
        ]);
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forProject($project1)->forOrganization($data->organization)->create([
            'start' => Carbon::now()->subDays(3),
        ]);
        TimeEntry::factory()->forMember($data->member)->forProject($project1)->forOrganization($data->organization)->create([
            'start' => Carbon::now()->subDays(4),
        ]);
        TimeEntry::factory()->forMember($data->member)->forProject($project2)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index', [
            $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
            'project_ids' => [$project1->getKey()],
            'limit' => 1,
            'offset' => 1,
            'only_full_dates' => 'false',
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('data.*.id', [$timeEntry->getKey()]);
    }

    public function test_index_export_endpoint_fails_if_user_has_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_export_endpoint_fails_if_pdf_renderer_is_not_configured_but_a_user_want_a_pdf_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);
        Config::set('services.gotenberg.url', null);
        $this->actAsOrganizationWithSubscription();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'pdf_renderer_is_not_configured',
            'message' => 'PDF renderer is not configured',
        ]);
    }

    public function test_index_export_endpoint_fails_if_user_wants_a_pdf_export_but_has_no_subscription(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithoutSubscriptionAndWithoutTrial();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'feature_is_not_available_in_free_plan',
            'message' => 'Feature is not available in free plan',
        ]);
    }

    public function test_index_export_endpoint_fails_if_user_has_only_access_to_own_time_entries_but_does_not_filter_for_this(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_export_endpoint_can_create_a_detailed_time_entry_report_in_format_csv(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_index_export_endpoint_can_create_a_detailed_time_entry_report_in_format_ods(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::ODS,
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_index_export_endpoint_can_create_a_detailed_time_entry_report_in_format_xlxs(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::XLSX,
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_index_export_endpoint_can_create_a_detailed_time_entry_report_in_format_pdf(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithSubscription();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.index-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_aggregate_export_endpoints_fails_if_user_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
            'group' => TimeEntryAggregationType::Client,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_aggregate_endpoint_fails_if_user_has_no_permission_to_view_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_aggregate_export_endpoint_fails_if_user_wants_a_pdf_export_but_has_no_subscription(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithoutSubscriptionAndWithoutTrial();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
            'group' => TimeEntryAggregationType::Client,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'feature_is_not_available_in_free_plan',
            'message' => 'Feature is not available in free plan',
        ]);
    }

    public function test_aggregate_export_endpoint_fails_if_user_has_only_access_to_own_time_entries_but_does_not_filter_for_this(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
            'group' => TimeEntryAggregationType::Client,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_aggregate_export_endpoints_can_create_a_csv_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::CSV,
            'group' => TimeEntryAggregationType::Client,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_aggregate_export_endpoints_can_create_a_xlsx_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::XLSX,
            'group' => TimeEntryAggregationType::Client,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_aggregate_export_endpoints_can_create_a_ods_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::ODS,
            'group' => TimeEntryAggregationType::User,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_aggregate_export_endpoint_fails_if_pdf_renderer_is_not_configured_but_a_user_want_a_pdf_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithSubscription();
        Config::set('services.gotenberg.url', null);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
            'group' => TimeEntryAggregationType::User,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'pdf_renderer_is_not_configured',
            'message' => 'PDF renderer is not configured',
        ]);
    }

    public function test_aggregate_export_endpoints_can_create_a_pdf_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->startWithDuration(Carbon::now(), 100)->create();
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithSubscription();

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate-export', [
            $data->organization->getKey(),
            'format' => ExportFormat::PDF,
            'group' => TimeEntryAggregationType::User,
            'sub_group' => TimeEntryAggregationType::Project,
            'history_group' => TimeEntryAggregationTypeInterval::Month,
            'start' => Carbon::now()->startOfYear()->toIso8601ZuluString(),
            'end' => Carbon::now()->endOfYear()->toIso8601ZuluString(),
        ]));

        // Assert
        $this->assertResponseCode($response, 200);
    }

    public function test_aggregate_endpoint_fails_if_user_has_only_access_to_own_time_entries_but_does_not_filter_for_this(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'day',
            'sub_group' => 'project',
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_aggregate_endpoint_fails_if_request_has_sub_group_but_no_group(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'sub_group' => TimeEntryAggregationType::Task->value,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('group');
    }

    public function test_aggregate_endpoint_works_for_user_with_only_access_to_own_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:own',
        ]);
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->create();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $start = Carbon::now()->timezone($data->user->timezone)->subDays(2);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->startWithDuration($start, 100)->create();
        $timeEntryOtherMember = TimeEntry::factory()->forOrganization($data->organization)->forMember($otherMember)->forProject($project)->startWithDuration($start, 100)->create();

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
            'group' => 'project',
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson([
            'data' => [
                'seconds' => 100,
                'cost' => 0,
                'grouped_data' => [
                    0 => [
                        'key' => $project->getKey(),
                        'seconds' => 100,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                ],
                'grouped_type' => 'project',
            ],
        ]);
    }

    public function test_aggregate_endpoint_groups_by_two_groups(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $day1 = Carbon::now()->timezone($data->user->timezone)->subDays(1);
        $day2 = Carbon::now()->timezone($data->user->timezone)->subDays(3);
        $timeEntry1NoProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($day1, 10)->create();
        $timeEntry2NoProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($day2, 10)->create();
        $timeEntry1WithProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->startWithDuration($day1, 10)->create();
        $timeEntry2WithProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->startWithDuration($day2, 10)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'day',
            'sub_group' => 'project',
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson([
            'data' => [
                'seconds' => 40,
                'cost' => 0,
                'grouped_data' => [
                    0 => [
                        'key' => $day2->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => 'project',
                        'grouped_data' => [
                            0 => [
                                'key' => $project->getKey(),
                                'seconds' => 10,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                            ],
                            1 => [
                                'key' => null,
                                'seconds' => 10,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                            ],
                        ],
                    ],
                    1 => [
                        'key' => $day1->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => 'project',
                        'grouped_data' => [
                            0 => [
                                'key' => $project->getKey(),
                                'seconds' => 10,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                            ],
                            1 => [
                                'key' => null,
                                'seconds' => 10,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                            ],
                        ],
                    ],
                ],
                'grouped_type' => 'day',
            ],
        ]);
    }

    public function test_aggregate_endpoint_groups_by_two_groups_with_fill_gaps_argument(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $day1 = Carbon::now()->timezone($data->user->timezone)->subDays(1);
        $day2 = Carbon::now()->timezone($data->user->timezone)->subDays(3);
        $timeEntry1NoProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($day1, 10)->create();
        $timeEntry2NoProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($day2, 10)->create();
        $timeEntry1WithProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->startWithDuration($day1, 10)->create();
        $timeEntry2WithProject = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->startWithDuration($day2, 10)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'project',
            'sub_group' => 'day',
            'fill_gaps_in_time_groups' => 'true',
            'start' => $day2->copy()->subSecond()->toIso8601ZuluString(),
            'end' => $day1->copy()->addSecond()->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson(['data' => [
            'seconds' => 40,
            'cost' => 0,
            'grouped_type' => 'project',
            'grouped_data' => [
                0 => [
                    'key' => $project->getKey(),
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'day',
                    'grouped_data' => [
                        0 => [
                            'key' => $day2->format('Y-m-d'),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        1 => [
                            'key' => $day2->copy()->addDay()->format('Y-m-d'),
                            'seconds' => 0,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        2 => [
                            'key' => $day1->format('Y-m-d'),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
                1 => [
                    'key' => null,
                    'seconds' => 20,
                    'cost' => 0,
                    'grouped_type' => 'day',
                    'grouped_data' => [
                        0 => [
                            'key' => $day2->format('Y-m-d'),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        1 => [
                            'key' => $day2->copy()->addDay()->format('Y-m-d'),
                            'seconds' => 0,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                        2 => [
                            'key' => $day1->format('Y-m-d'),
                            'seconds' => 10,
                            'cost' => 0,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ],
                    ],
                ],
            ],
        ],
        ]);
    }

    public function test_aggregate_endpoint_groups_by_one_group(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $week1 = Carbon::now()->timezone($data->user->timezone)->startOfWeek($data->user->week_start->carbonWeekDay());
        $week2 = Carbon::now()->timezone($data->user->timezone)->subWeeks(2)->startOfWeek($data->user->week_start->carbonWeekDay());
        $timeEntry1Week1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($week1->copy()->addDays(1), 10)->create();
        $timeEntry2Week1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($week1->copy()->addDays(2), 10)->create();
        $timeEntry1Week2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($week2->copy()->addDays(3), 10)->create();
        $timeEntry2Week2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($week2->copy()->addDays(4), 10)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'week',
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson([
            'data' => [
                'seconds' => 40,
                'cost' => 0,
                'grouped_type' => 'week',
                'grouped_data' => [
                    0 => [
                        'key' => $week2->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                    1 => [
                        'key' => $week1->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                ],
            ],
        ]);
    }

    public function test_aggregate_endpoint_groups_by_one_group_with_fill_gaps_argument(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $laterWeekEnd = Carbon::now()->timezone($data->user->timezone)->endOfWeek($data->user->week_start->toEndOfWeek()->carbonWeekDay());
        $earlierWeekStart = Carbon::now()->timezone($data->user->timezone)->subWeeks(2)->startOfWeek($data->user->week_start->carbonWeekDay());

        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($laterWeekEnd->copy()->subDays(1), 10)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($laterWeekEnd->copy()->subDays(2), 10)->create();
        $timeEntry3 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($earlierWeekStart->copy()->addDays(1), 10)->create();
        $timeEntry4 = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($earlierWeekStart->copy()->addDays(2), 10)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
            'group' => 'week',
            'fill_gaps_in_time_groups' => 'true',
            'start' => $earlierWeekStart->toIso8601ZuluString(),
            'end' => $laterWeekEnd->toIso8601ZuluString(),
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson([
            'data' => [
                'seconds' => 40,
                'cost' => 0,
                'grouped_type' => 'week',
                'grouped_data' => [
                    0 => [
                        'key' => $earlierWeekStart->startOfWeek($data->user->week_start->carbonWeekDay())->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                    1 => [
                        'key' => $laterWeekEnd->copy()->subWeek()->startOfWeek($data->user->week_start->carbonWeekDay())->format('Y-m-d'),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                    2 => [
                        'key' => $laterWeekEnd->startOfWeek($data->user->week_start->carbonWeekDay())->format('Y-m-d'),
                        'seconds' => 20,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                    ],
                ],
            ],
        ]
        );
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
        $data = $this->createUserWithPermission();
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

    public function test_store_endpoint_fails_gracefully_if_non_uuid_text_is_in_uuid_validated_field_in_body(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->withTask($data->organization)->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'member_id' => 'non-uuid-text',
            'tags' => ['non-uuid-text', 1],
            'project_id' => 'non-uuid-text',
            'task_id' => 'non-uuid-text',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    public function test_store_endpoints_sets_billable_rate(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $timeEntryFake = TimeEntry::factory()->withTask($data->organization)->forOrganization($data->organization)->make();
        $project = Project::factory()->forOrganization($data->organization)->billable()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'billable' => true,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'member_id' => $data->member->getKey(),
            'task_id' => null,
            'project_id' => $project->getKey(),
            'billable_rate' => $project->billable_rate,
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

    public function test_store_endpoint_can_create_new_time_entry_with_project_and_automatically_set_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'billable' => $timeEntryFake->billable,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $response->json('data.id'),
            'member_id' => $data->member->getKey(),
            'task_id' => null,
            'project_id' => $project->getKey(),
            'client_id' => $client->getKey(),
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

    public function test_create_endpoint_recalculates_project_and_task_spent_time_if_time_entry_has_project_and_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create();
        TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->forTask($task)->create();
        $timeEntryFake = TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'description' => $timeEntryFake->description,
            'billable' => $timeEntryFake->billable,
            'start' => Carbon::now()->toIso8601ZuluString(),
            'end' => Carbon::now()->addHour()->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, function (RecalculateSpentTimeForProject $job) use ($project): bool {
            return $job->project->is($project);
        });
        Queue::assertPushed(RecalculateSpentTimeForTask::class, function (RecalculateSpentTimeForTask $job) use ($task): bool {
            return $job->task->is($task);
        });
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_own_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
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
        $this->assertResponseCode($response, 200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $data->member->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoints_sets_billable_rate(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        $timeEntryFake = TimeEntry::factory()->withTags($data->organization)->forOrganization($data->organization)->make();
        $project = Project::factory()->forOrganization($data->organization)->billable()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'billable' => true,
            'start' => $timeEntryFake->start->toIso8601ZuluString(),
            'end' => $timeEntryFake->end->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $this->assertResponseCode($response, 200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $data->member->getKey(),
            'task_id' => null,
            'project_id' => $project->getKey(),
            'billable_rate' => $project->billable_rate,
        ]);
    }

    public function test_update_endpoint_updates_time_entry_for_current_user_but_does_not_send_member_id(): void
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
        ]);

        // Assert
        $this->assertResponseCode($response, 200);
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
        $this->assertResponseCode($response, 200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $member->getKey(),
            'task_id' => $timeEntryFake->task_id,
        ]);
    }

    public function test_update_endpoint_can_update_project_and_automatically_set_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $user = User::factory()->create();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $member->getKey(),
            'task_id' => $timeEntry->task_id,
            'project_id' => $project->getKey(),
            'client_id' => $client->getKey(),
        ]);
    }

    public function test_update_endpoint_can_removed_project_from_time_entry_and_automatically_remove_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $user = User::factory()->create();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->forProject($project)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'project_id' => null,
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'member_id' => $member->getKey(),
            'task_id' => null,
            'project_id' => null,
            'client_id' => null,
        ]);
    }

    public function test_update_endpoint_recalculates_project_and_task_spend_time_after_updating_time_entry_settings_a_project_and_a_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forProject(null)->forTask(null)->forMember($data->member)->create();
        TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
        ]);

        // Assert
        $this->assertResponseCode($response, 200);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
        Queue::assertPushed(function (RecalculateSpentTimeForProject $job) use ($project): bool {
            return $job->project->is($project);
        }, 1);
        Queue::assertPushed(function (RecalculateSpentTimeForTask $job) use ($task): bool {
            return $job->task->is($task);
        }, 1);
    }

    public function test_update_endpoint_recalculates_project_and_task_spend_time_after_updating_time_entry_settings_a_new_project_and_a_new_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $oldProject = Project::factory()->forOrganization($data->organization)->create();
        $oldTask = Task::factory()->forOrganization($data->organization)->forProject($oldProject)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forProject($oldProject)->forTask($oldTask)->forMember($data->member)->create();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create();
        TimeEntry::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $timeEntry->getKey()]), [
            'project_id' => $project->getKey(),
            'task_id' => $task->getKey(),
        ]);

        // Assert
        $this->assertResponseCode($response, 200);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 2);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 2);
        Queue::assertPushed(function (RecalculateSpentTimeForProject $job) use ($project): bool {
            return $job->project->is($project);
        }, 1);
        Queue::assertPushed(function (RecalculateSpentTimeForProject $job) use ($oldProject): bool {
            return $job->project->is($oldProject);
        }, 1);
        Queue::assertPushed(function (RecalculateSpentTimeForTask $job) use ($task): bool {
            return $job->task->is($task);
        }, 1);
        Queue::assertPushed(function (RecalculateSpentTimeForTask $job) use ($oldTask): bool {
            return $job->task->is($oldTask);
        }, 1);
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
        $data = $this->createUserWithPermission();
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

    public function test_destroy_multiple_endpoint_fails_if_user_has_no_permission_to_delete_own_time_entries_or_all_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $timeEntries = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy-multiple', [$data->organization->getKey()]), [
            'ids' => $timeEntries->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertValid();
        $response->assertForbidden();
    }

    public function test_destroy_multiple_endpoint_fails_if_ids_contains_non_uuid_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy-multiple', [$data->organization->getKey()]), [
            'ids' => [
                Str::uuid(),
                'non-uuid',
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'ids.1' => ['The ids.1 field must be a valid UUID.'],
        ]);
    }

    public function test_destroy_multiple_endpoint_own_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_own_time_entries_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
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
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
        ]);
    }

    public function test_destroy_multiple_deletes_all_time_entries_and_fails_for_time_entries_of_other_users_and_and_other_organizations_with_all_time_entries_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:all',
        ]);
        $otherData = $this->createUserWithPermission();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser($otherUser)->role(Role::Employee)->create();

        $ownTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->create();
        $otherOrganizationTimeEntry = TimeEntry::factory()->forMember($otherData->member)->create();
        $wrongId = Str::uuid();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $ownTimeEntry->getKey(),
                $otherTimeEntry->getKey(),
                $otherOrganizationTimeEntry->getKey(),
                $wrongId,
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
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
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $ownTimeEntry->getKey(),
        ]);
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherOrganizationTimeEntry->getKey(),
        ]);
    }

    public function test_destroy_multiple_recalculates_project_and_task_spend_time_after_deleting_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:all',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->create();
        $timeEntryWithProject = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forMember($data->member)->create();
        $timeEntryWithTask = TimeEntry::factory()->forOrganization($data->organization)->forTask($task)->forMember($data->member)->create();
        $timeEntryWithProjectAndTask = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forTask($task)->forMember($data->member)->create();
        $timeEntryWithoutProjectAndTask = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create();
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntryWithProject->getKey(),
                $timeEntryWithTask->getKey(),
                $timeEntryWithProjectAndTask->getKey(),
                $timeEntryWithoutProjectAndTask->getKey(),
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntryWithProject->getKey(),
                $timeEntryWithTask->getKey(),
                $timeEntryWithProjectAndTask->getKey(),
                $timeEntryWithoutProjectAndTask->getKey(),
            ],
            'error' => [
            ],
        ]);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 3);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 2);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, function (RecalculateSpentTimeForProject $job) use ($project) {
            return $job->project->is($project);
        });
        Queue::assertPushed(RecalculateSpentTimeForTask::class, function (RecalculateSpentTimeForTask $job) use ($task) {
            return $job->task->is($task);
        });
    }

    public function test_destroy_endpoint_recalculates_project_and_task_spend_time_after_deleting_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forProject($project)->forTask($task)->forMember($data->member)->create();
        $project = $timeEntry->project;
        $task = $timeEntry->task;
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, function (RecalculateSpentTimeForProject $job) use ($project) {
            return $job->project->is($project);
        });
        Queue::assertPushed(RecalculateSpentTimeForTask::class, function (RecalculateSpentTimeForTask $job) use ($task) {
            return $job->task->is($task);
        });
    }

    public function test_destroy_endpoint_does_not_recalculate_project_and_task_spend_time_after_deleting_time_entry_if_time_entry_had_no_project_and_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:delete:own',
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forProject(null)->forTask(null)->forMember($data->member)->create();
        Passport::actingAs($data->user);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.time-entries.destroy', [$data->organization->getKey(), $timeEntry->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
        Queue::assertNotPushed(RecalculateSpentTimeForProject::class);
        Queue::assertNotPushed(RecalculateSpentTimeForTask::class);
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

    public function test_update_multiple_remove_task_from_time_entries_only_if_project_is_set_to_a_new_value_without_setting_a_new_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $project1 = Project::factory()->forOrganization($data->organization)->create();
        $project2 = Project::factory()->forOrganization($data->organization)->create();
        $task1 = Task::factory()->forProject($project1)->forOrganization($data->organization)->create();
        $task2 = Task::factory()->forProject($project2)->forOrganization($data->organization)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project1)->forTask($task1)->forMember($data->member)->create();
        $timeEntry2 = TimeEntry::factory()->forOrganization($data->organization)->forProject($project2)->forTask($task2)->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'project_id' => $project2->getKey(),
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry1->getKey(),
            'project_id' => $project2->getKey(),
            'task_id' => null,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry2->getKey(),
            'project_id' => $project2->getKey(),
            'task_id' => $task2->getKey(),
        ]);
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
        $this->assertResponseCode($response, 200);
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
        $this->assertResponseCode($response, 200);
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

    public function test_update_multiple_updates_sets_description_to_empty_if_the_client_sends_null(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->create([
            'description' => '',
        ]);
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->create([
            'description' => 'test',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'description' => null,
            ],
        ]);

        // Assert
        $response->assertValid();
        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry1->getKey(),
            'description' => '',
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry2->getKey(),
            'description' => '',
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
        $this->assertResponseCode($response, 200);
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
        $response = $this->withoutExceptionHandling()->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
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
        $this->assertResponseCode($response, 200);
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

    public function test_update_multiple_refreshes_billable_rate_on_updates_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);

        $oldProject = Project::factory()->forOrganization($data->organization)->billable()->create();
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->forProject($oldProject)->billable()->create();
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->forProject($oldProject)->notBillable()->create();
        $project = Project::factory()->billable()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'project_id' => $project->getKey(),
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry1->getKey(),
            'project_id' => $project->getKey(),
            'billable' => true,
            'billable_rate' => $project->billable_rate,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry2->getKey(),
            'project_id' => $project->getKey(),
            'billable' => false,
            'billable_rate' => null,
        ]);
    }

    public function test_update_multiple_ignores_other_fields_in_changes(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->create();
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->create();
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'project_id' => $project->getKey(),
                'other_field' => 'test123',
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [
            ],
        ]);
    }

    public function test_update_multiple_can_update_project_and_sets_client_automatically(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);

        $oldClient = Client::factory()->forOrganization($data->organization)->create();
        $oldProject = Project::factory()->forOrganization($data->organization)->forClient($oldClient)->create();
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->forProject($oldProject)->create();
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->create();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forClient($client)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'project_id' => $project->getKey(),
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry1->getKey(),
            'client_id' => $client->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => null,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry2->getKey(),
            'client_id' => $client->getKey(),
            'project_id' => $project->getKey(),
            'task_id' => null,
        ]);
    }

    public function test_update_multiple_can_remove_project_from_time_entries_and_sets_client_automatically(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:all',
        ]);

        $oldClient = Client::factory()->forOrganization($data->organization)->create();
        $oldProject = Project::factory()->forOrganization($data->organization)->forClient($oldClient)->create();
        $timeEntry1 = TimeEntry::factory()->forMember($data->member)->forProject($oldProject)->create();
        $timeEntry2 = TimeEntry::factory()->forMember($data->member)->create();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forClient($client)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'changes' => [
                'project_id' => null,
            ],
        ]);

        // Assert
        $response->assertValid();
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'success' => [
                $timeEntry1->getKey(),
                $timeEntry2->getKey(),
            ],
            'error' => [
            ],
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry1->getKey(),
            'client_id' => null,
            'project_id' => null,
            'task_id' => null,
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry2->getKey(),
            'client_id' => null,
            'project_id' => null,
            'task_id' => null,
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
