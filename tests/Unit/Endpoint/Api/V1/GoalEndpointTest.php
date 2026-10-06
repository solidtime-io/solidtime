<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Role;
use App\Enums\TagMatchType;
use App\Enums\TimeEntryType;
use App\Enums\Weekday;
use App\Http\Controllers\Api\V1\GoalController;
use App\Models\Client;
use App\Models\Goal;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\BillingContract;
use App\Service\Dto\GoalFiltersDto;
use App\Service\GoalsContract;
use App\Service\TimeEntryFilter;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(GoalController::class)]
class GoalEndpointTest extends ApiEndpointTestAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        // Core rules only, independent of whether the Goals extension is enabled locally
        $this->app->bind(GoalsContract::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalGoalBody(): array
    {
        return [
            'name' => 'Deep work',
            'type' => GoalType::Personal->value,
            'comparison' => GoalComparison::AtLeast->value,
            'target_seconds' => 10 * 3600,
            'period' => GoalPeriod::Week->value,
        ];
    }

    /**
     * Organizations without a paid plan can only have a limited number of goals.
     */
    private function limitGoalsTo(?int $limit): void
    {
        $this->app->bind(BillingContract::class, fn (): BillingContract => new class($limit) extends BillingContract
        {
            public function __construct(private readonly ?int $limit) {}

            public function getGoalLimit(Organization $organization): ?int
            {
                return $this->limit;
            }
        });
    }

    public function test_index_endpoint_fails_if_user_does_not_have_permission_to_view_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Goal::factory()->forMember($data->member)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_succeeds_with_only_the_organization_type_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:organization-type']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
    }

    public function test_show_endpoint_fails_for_an_organization_goal_with_only_the_own_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $goal = Goal::factory()->organizationGoalForMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_for_a_personal_goal_of_another_member_with_only_the_organization_type_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:organization-type']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $goal = Goal::factory()->forMember($otherMember)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_only_own_goals_of_organization_ordered_by_created_at_desc(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $otherOrganization = Organization::factory()->create();
        $ownGoals = Goal::factory()->forMember($data->member)->randomCreatedAt()->createMany(3);
        Goal::factory()->forMember($otherMember)->createMany(2);
        Goal::factory()->forOrganization($otherOrganization)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $expected = Goal::query()->whereKey($ownGoals->pluck('id'))->orderBy('created_at', 'desc')->orderBy('id')->get();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 3)
            ->where('data.0.id', $expected->get(0)->getKey())
            ->where('data.1.id', $expected->get(1)->getKey())
            ->where('data.2.id', $expected->get(2)->getKey())
        );
    }

    public function test_index_endpoint_returns_goal_with_filters_and_current_progress(): void
    {
        // Arrange
        $this->travelTo(Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC'));
        $data = $this->createUserWithPermission(['goals:view:own']);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setProjectIds([$project->getKey()]);
        $filters->timeEntryType = TimeEntryType::Work;
        $goal = Goal::factory()->forMember($data->member)->period(GoalPeriod::Day)->atLeast(3600)->filters($filters)->create();
        TimeEntry::factory()->forMember($data->member)->forProject($project)
            ->startWithDuration(Carbon::now()->subHours(3), 1200)->create();
        TimeEntry::factory()->forMember($data->member)
            ->startWithDuration(Carbon::now()->subHours(3), 1200)->create();
        TimeEntry::factory()->forMember($data->member)->forProject($project)
            ->start(Carbon::now()->subMinutes(10))->active()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->count('data', 1)
            ->where('data.0.id', $goal->getKey())
            ->where('data.0.name', $goal->name)
            ->where('data.0.type', GoalType::Personal->value)
            ->where('data.0.comparison', GoalComparison::AtLeast->value)
            ->where('data.0.target_seconds', 3600)
            ->where('data.0.period', GoalPeriod::Day->value)
            ->where('data.0.member_id', $data->member->getKey())
            ->where('data.0.timezone', 'UTC')
            ->where('data.0.week_start', Weekday::Monday->value)
            ->where('data.0.is_archived', false)
            ->where('data.0.filters.member_ids', null)
            ->where('data.0.filters.project_ids', [$project->getKey()])
            ->where('data.0.filters.task_ids', null)
            ->where('data.0.filters.tag_ids', null)
            ->where('data.0.filters.tag_match_type', null)
            ->where('data.0.filters.client_ids', null)
            ->where('data.0.filters.billable', null)
            ->where('data.0.filters.time_entry_type', TimeEntryType::Work->value)
            ->where('data.0.progress.period_start', '2024-01-03T00:00:00Z')
            ->where('data.0.progress.period_end', '2024-01-04T00:00:00Z')
            ->where('data.0.progress.tracked_seconds', 1200 + 600)
            ->where('data.0.progress.status', GoalStatus::InProgress->value)
            ->etc()
        );
    }

    public function test_index_endpoint_returns_the_progress_of_each_goal(): void
    {
        // Arrange
        $this->travelTo(Carbon::create(2024, 1, 3, 12, 0, 0, 'UTC'));
        $data = $this->createUserWithPermission(['goals:view:own']);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setProjectIds([$project->getKey()]);
        $dayGoal = Goal::factory()->forMember($data->member)->period(GoalPeriod::Day)->atLeast(3600)->filters($filters)->create([
            'created_at' => Carbon::now()->subDay(),
        ]);
        $weekGoal = Goal::factory()->forMember($data->member)->period(GoalPeriod::Week)->atLeast(3600)->create([
            'created_at' => Carbon::now()->subDays(2),
        ]);
        TimeEntry::factory()->forMember($data->member)->forProject($project)
            ->startWithDuration(Carbon::now()->subHours(3), 1200)->create();
        TimeEntry::factory()->forMember($data->member)
            ->startWithDuration(Carbon::now()->subDay(), 1800)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->count('data', 2)
            ->where('data.0.id', $dayGoal->getKey())
            ->where('data.0.progress.period_start', '2024-01-03T00:00:00Z')
            ->where('data.0.progress.period_end', '2024-01-04T00:00:00Z')
            ->where('data.0.progress.tracked_seconds', 1200)
            ->where('data.1.id', $weekGoal->getKey())
            ->where('data.1.progress.period_start', '2024-01-01T00:00:00Z')
            ->where('data.1.progress.period_end', '2024-01-08T00:00:00Z')
            ->where('data.1.progress.tracked_seconds', 1200 + 1800)
            ->etc()
        );
    }

    public function test_index_endpoint_does_not_show_organization_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $ownGoal = Goal::factory()->forMember($data->member)->create();
        Goal::factory()->organizationGoalForMember($data->member)->create();
        Goal::factory()->forOrganization($data->organization)->forEveryMember()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ownGoal->getKey());
        $response->assertJsonPath('data.0.type', GoalType::Personal->value);
    }

    public function test_index_endpoint_can_filter_by_type(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $personalResponse = $this->getJson(route('api.v1.goals.index', [
            'organization' => $data->organization->getKey(),
            'type' => GoalType::Personal->value,
        ]));
        $organizationResponse = $this->getJson(route('api.v1.goals.index', [
            'organization' => $data->organization->getKey(),
            'type' => GoalType::Organization->value,
        ]));

        // Assert
        $personalResponse->assertOk();
        $personalResponse->assertJsonCount(1, 'data');
        $personalResponse->assertJsonPath('data.0.id', $goal->getKey());
        $organizationResponse->assertOk();
        $organizationResponse->assertJsonCount(0, 'data');
    }

    public function test_index_endpoint_fails_with_validation_errors_for_invalid_filters(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.index', [
            'organization' => $data->organization->getKey(),
            'type' => 'team',
            'archived' => 'maybe',
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type', 'archived']);
    }

    public function test_index_endpoint_hides_archived_goals_per_default_and_can_filter_by_archived(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        $archivedGoal = Goal::factory()->forMember($data->member)->archived()->create();
        Passport::actingAs($data->user);

        // Act
        $defaultResponse = $this->getJson(route('api.v1.goals.index', [$data->organization->getKey()]));
        $archivedResponse = $this->getJson(route('api.v1.goals.index', [
            'organization' => $data->organization->getKey(),
            'archived' => 'true',
        ]));
        $allResponse = $this->getJson(route('api.v1.goals.index', [
            'organization' => $data->organization->getKey(),
            'archived' => 'all',
        ]));

        // Assert
        $defaultResponse->assertOk();
        $defaultResponse->assertJsonCount(1, 'data');
        $defaultResponse->assertJsonPath('data.0.id', $goal->getKey());
        $archivedResponse->assertOk();
        $archivedResponse->assertJsonCount(1, 'data');
        $archivedResponse->assertJsonPath('data.0.id', $archivedGoal->getKey());
        $archivedResponse->assertJsonPath('data.0.is_archived', true);
        $allResponse->assertOk();
        $allResponse->assertJsonCount(2, 'data');
    }

    public function test_show_endpoint_fails_if_user_does_not_have_permission_to_view_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_if_goal_belongs_to_other_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $goal = Goal::factory()->forMember($otherMember)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_if_goal_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $goal = Goal::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_own_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:view:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.goals.show', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.id', $goal->getKey())
            ->where('data.name', $goal->name)
            ->has('data.progress.tracked_seconds')
            ->has('data.progress.status')
            ->etc()
        );
    }

    public function test_store_endpoint_fails_if_user_does_not_have_permission_to_create_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_creates_personal_goal_with_minimal_body_for_the_current_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $data->user->timezone = 'Europe/Vienna';
        $data->user->week_start = Weekday::Sunday;
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $this->assertResponseCode($response, 201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.name', 'Deep work')
            ->where('data.type', GoalType::Personal->value)
            ->where('data.comparison', GoalComparison::AtLeast->value)
            ->where('data.target_seconds', 36000)
            ->where('data.period', GoalPeriod::Week->value)
            ->where('data.member_id', $data->member->getKey())
            ->where('data.is_archived', false)
            // The timezone and the week start of the current user are pinned on the goal
            ->where('data.timezone', 'Europe/Vienna')
            ->where('data.week_start', Weekday::Sunday->value)
            ->where('data.filters.project_ids', null)
            ->where('data.filters.time_entry_type', null)
            ->where('data.progress.tracked_seconds', 0)
            ->where('data.progress.status', GoalStatus::InProgress->value)
            ->etc()
        );
        $this->assertDatabaseHas(Goal::class, [
            'id' => $response->json('data.id'),
            'name' => 'Deep work',
            'type' => GoalType::Personal->value,
            'organization_id' => $data->organization->getKey(),
            'member_id' => $data->member->getKey(),
            'archived_at' => null,
        ]);
    }

    public function test_store_endpoint_creates_goal_with_an_explicit_timezone_and_week_start(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'timezone' => 'America/New_York',
            'week_start' => Weekday::Saturday->value,
        ]));

        // Assert
        $this->assertResponseCode($response, 201);
        $response->assertJsonPath('data.timezone', 'America/New_York');
        $response->assertJsonPath('data.week_start', Weekday::Saturday->value);
    }

    public function test_store_endpoint_creates_goal_with_all_filters(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create();
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), [
            'name' => 'Less meetings',
            'type' => GoalType::Personal->value,
            'comparison' => GoalComparison::LessThan->value,
            'target_seconds' => 5 * 3600,
            'period' => GoalPeriod::Month->value,
            'member_id' => $data->member->getKey(),
            'filters' => [
                'project_ids' => [$project->getKey(), TimeEntryFilter::NONE_VALUE],
                'task_ids' => [$task->getKey()],
                'tag_ids' => [$tag->getKey()],
                'tag_match_type' => TagMatchType::NotContains->value,
                'client_ids' => [$client->getKey()],
                'billable' => false,
                'time_entry_type' => TimeEntryType::Work->value,
            ],
        ]);

        // Assert
        $this->assertResponseCode($response, 201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.comparison', GoalComparison::LessThan->value)
            ->where('data.period', GoalPeriod::Month->value)
            ->where('data.filters.project_ids', [$project->getKey(), TimeEntryFilter::NONE_VALUE])
            ->where('data.filters.task_ids', [$task->getKey()])
            ->where('data.filters.tag_ids', [$tag->getKey()])
            ->where('data.filters.tag_match_type', TagMatchType::NotContains->value)
            ->where('data.filters.client_ids', [$client->getKey()])
            ->where('data.filters.billable', false)
            ->where('data.filters.time_entry_type', TimeEntryType::Work->value)
            ->where('data.progress.status', GoalStatus::OnTrack->value)
            ->etc()
        );
        /** @var Goal $goal */
        $goal = Goal::query()->findOrFail($response->json('data.id'));
        $this->assertSame([$project->getKey(), TimeEntryFilter::NONE_VALUE], $goal->filters->projectIds?->toArray());
        $this->assertSame(TagMatchType::NotContains, $goal->filters->tagMatchType);
        $this->assertFalse($goal->filters->billable);
    }

    public function test_store_endpoint_fails_with_validation_errors_for_invalid_body(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), [
            'name' => '',
            'type' => 'team',
            'comparison' => 'more_than',
            'target_seconds' => 0,
            'period' => 'year',
            'timezone' => 'Mars/Phobos',
            'week_start' => 'caturday',
            'filters' => [
                'project_ids' => ['not-a-uuid'],
                'tag_match_type' => 'maybe',
                'time_entry_type' => 'lunch',
            ],
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'name',
            'type',
            'comparison',
            'target_seconds',
            'period',
            'timezone',
            'week_start',
            'filters.project_ids.0',
            'filters.tag_match_type',
            'filters.time_entry_type',
        ]);
    }

    public function test_store_endpoint_fails_if_filter_ids_belong_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($otherOrganization)->create();
        $tag = Tag::factory()->forOrganization($otherOrganization)->create();
        $client = Client::factory()->forOrganization($otherOrganization)->create();
        $task = Task::factory()->forOrganization($otherOrganization)->forProject($project)->create();
        $memberOfOtherOrganization = Member::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'filters' => [
                'member_ids' => [$memberOfOtherOrganization->getKey()],
                'project_ids' => [$project->getKey()],
                'task_ids' => [$task->getKey()],
                'tag_ids' => [$tag->getKey()],
                'client_ids' => [$client->getKey()],
            ],
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'filters.member_ids.0',
            'filters.project_ids.0',
            'filters.task_ids.0',
            'filters.tag_ids.0',
            'filters.client_ids.0',
        ]);
    }

    public function test_store_endpoint_fails_with_validation_error_for_a_personal_goal_for_other_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'member_id' => $otherMember->getKey(),
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_for_organization_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $responseForEveryMember = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => null,
        ]));
        $responseForSelf = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => $data->member->getKey(),
        ]));

        // Assert
        $responseForEveryMember->assertForbidden();
        $responseForSelf->assertForbidden();
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_for_organization_goals_with_the_organization_type_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:organization-type']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $responseForEveryMember = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => null,
        ]));
        $responseForOtherMember = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => $otherMember->getKey(),
        ]));

        // Assert
        $responseForEveryMember->assertForbidden();
        $responseForOtherMember->assertForbidden();
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_with_validation_error_for_a_personal_goal_with_a_member_ids_filter(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'filters' => [
                'member_ids' => [$data->member->getKey()],
            ],
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filters.member_ids']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_stores_empty_id_filters_as_null(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'filters' => [
                'member_ids' => [],
                'project_ids' => [],
                'task_ids' => [],
                'tag_ids' => [],
                'client_ids' => [],
            ],
        ]));

        // Assert
        $this->assertResponseCode($response, 201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.filters.member_ids', null)
            ->where('data.filters.project_ids', null)
            ->where('data.filters.task_ids', null)
            ->where('data.filters.tag_ids', null)
            ->where('data.filters.client_ids', null)
            ->etc()
        );
        /** @var Goal $goal */
        $goal = Goal::query()->findOrFail($response->json('data.id'));
        $this->assertNull($goal->filters->memberIds);
        $this->assertNull($goal->filters->projectIds);
        $this->assertNull($goal->filters->taskIds);
        $this->assertNull($goal->filters->tagIds);
        $this->assertNull($goal->filters->clientIds);
    }

    public function test_store_endpoint_fails_with_validation_error_for_a_personal_goal_without_a_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'member_id' => null,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_with_validation_error_for_invalid_member_id(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'member_id' => 'not-a-uuid',
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
    }

    public function test_store_endpoint_fails_with_validation_error_if_member_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $memberOfOtherOrganization = Member::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'member_id' => $memberOfOtherOrganization->getKey(),
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_with_validation_error_if_member_is_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:organization-type']);
        $placeholder = Member::factory()->forOrganization($data->organization)->role(Role::Placeholder)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => $placeholder->getKey(),
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_with_validation_error_if_member_ids_filter_contains_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:organization-type']);
        $member = Member::factory()->forOrganization($data->organization)->create();
        $placeholder = Member::factory()->forOrganization($data->organization)->role(Role::Placeholder)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), array_merge($this->minimalGoalBody(), [
            'type' => GoalType::Organization->value,
            'member_id' => null,
            'filters' => [
                'member_ids' => [$member->getKey(), $placeholder->getKey()],
            ],
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filters.member_ids.1']);
        $response->assertJsonMissingValidationErrors(['filters.member_ids', 'filters.member_ids.0']);
        $this->assertDatabaseCount(Goal::class, 0);
    }

    public function test_store_endpoint_fails_if_the_organization_reached_the_goal_limit_of_its_plan(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $this->limitGoalsTo(1);
        Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'feature_is_not_available_in_free_plan',
            'message' => 'Feature is not available in free plan',
        ]);
        $this->assertDatabaseCount(Goal::class, 1);
    }

    public function test_store_endpoint_counts_archived_goals_towards_the_goal_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $this->limitGoalsTo(1);
        Goal::factory()->forMember($data->member)->archived()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'feature_is_not_available_in_free_plan',
            'message' => 'Feature is not available in free plan',
        ]);
        $this->assertDatabaseCount(Goal::class, 1);
    }

    public function test_store_endpoint_creates_goal_if_the_organization_is_below_the_goal_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $this->limitGoalsTo(2);
        Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $this->assertResponseCode($response, 201);
        $this->assertDatabaseCount(Goal::class, 2);
    }

    public function test_store_endpoint_counts_the_goals_of_other_members_towards_the_goal_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $this->limitGoalsTo(1);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        Goal::factory()->forMember($otherMember)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('key', 'feature_is_not_available_in_free_plan');
        $this->assertDatabaseCount(Goal::class, 1);
    }

    public function test_store_endpoint_does_not_count_the_goals_of_other_organizations_towards_the_goal_limit(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:create:own']);
        $this->limitGoalsTo(1);
        Goal::factory()->forOrganization(Organization::factory()->create())->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.goals.store', [$data->organization->getKey()]), $this->minimalGoalBody());

        // Assert
        $this->assertResponseCode($response, 201);
        $this->assertDatabaseCount(Goal::class, 2);
    }

    public function test_update_endpoint_fails_if_user_does_not_have_permission_to_update_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_goal_belongs_to_other_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $goal = Goal::factory()->forMember($otherMember)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseMissing(Goal::class, ['name' => 'New name']);
    }

    public function test_update_endpoint_fails_if_goal_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_only_given_fields(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $filters = new GoalFiltersDto;
        $filters->billable = true;
        $goal = Goal::factory()->forMember($data->member)->atLeast(3600)->period(GoalPeriod::Day)->filters($filters)->create([
            'name' => 'Old name',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
            'target_seconds' => 7200,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.name', 'New name')
            ->where('data.target_seconds', 7200)
            ->where('data.comparison', GoalComparison::AtLeast->value)
            ->where('data.period', GoalPeriod::Day->value)
            ->where('data.filters.billable', true)
            ->etc()
        );
        $goal->refresh();
        $this->assertSame('New name', $goal->name);
        $this->assertSame(7200, $goal->target_seconds);
        $this->assertTrue($goal->filters->billable);
    }

    public function test_update_endpoint_can_change_the_timezone_and_the_week_start(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->timezone('UTC')->weekStart(Weekday::Monday)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'timezone' => 'Europe/Vienna',
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.timezone', 'Europe/Vienna');
        $response->assertJsonPath('data.week_start', Weekday::Sunday->value);
        $goal->refresh();
        $this->assertSame('Europe/Vienna', $goal->timezone);
        $this->assertSame(Weekday::Sunday, $goal->week_start);
    }

    public function test_update_endpoint_archives_and_unarchives_a_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $archiveResponse = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'is_archived' => true,
        ]);
        $archivedAt = $goal->refresh()->archived_at;
        $unarchiveResponse = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'is_archived' => false,
        ]);

        // Assert
        $archiveResponse->assertOk();
        $archiveResponse->assertJsonPath('data.is_archived', true);
        $this->assertNotNull($archivedAt);
        $unarchiveResponse->assertOk();
        $unarchiveResponse->assertJsonPath('data.is_archived', false);
        $this->assertNull($goal->refresh()->archived_at);
    }

    public function test_update_endpoint_replaces_filters_when_given(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $filters = new GoalFiltersDto;
        $filters->billable = true;
        $filters->setProjectIds([$project->getKey()]);
        $goal = Goal::factory()->forMember($data->member)->filters($filters)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'comparison' => GoalComparison::LessThan->value,
            'period' => GoalPeriod::Month->value,
            'filters' => [
                'time_entry_type' => TimeEntryType::Break->value,
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('data.comparison', GoalComparison::LessThan->value)
            ->where('data.period', GoalPeriod::Month->value)
            ->where('data.filters.project_ids', null)
            ->where('data.filters.billable', null)
            ->where('data.filters.time_entry_type', TimeEntryType::Break->value)
            ->etc()
        );
        $goal->refresh();
        $this->assertNull($goal->filters->projectIds);
        $this->assertNull($goal->filters->billable);
        $this->assertSame(TimeEntryType::Break, $goal->filters->timeEntryType);
    }

    public function test_update_endpoint_fails_for_stored_filter_ids_of_deleted_entities(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $tag = Tag::factory()->forOrganization($data->organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setProjectIds([$project->getKey()]);
        $filters->setTagIds([$tag->getKey()]);
        $goal = Goal::factory()->forMember($data->member)->filters($filters)->create(['name' => 'Old name']);
        $project->delete();
        $tag->delete();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
            'filters' => [
                'project_ids' => [$project->getKey()],
                'tag_ids' => [$tag->getKey()],
            ],
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filters.project_ids.0', 'filters.tag_ids.0']);
        $this->assertSame('Old name', $goal->refresh()->name);
    }

    public function test_update_endpoint_fails_for_filter_ids_of_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $filters = new GoalFiltersDto;
        $filters->setProjectIds([$project->getKey()]);
        $goal = Goal::factory()->forMember($data->member)->filters($filters)->create();
        $otherOrganizationProject = Project::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'filters' => [
                'project_ids' => [$project->getKey(), $otherOrganizationProject->getKey()],
            ],
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filters.project_ids.1']);
        $response->assertJsonMissingValidationErrors(['filters.project_ids.0']);
    }

    public function test_update_endpoint_fails_with_validation_error_if_the_type_is_sent(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'Renamed',
            'type' => GoalType::Organization->value,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type']);
        $goal->refresh();
        $this->assertSame('Old name', $goal->name);
        $this->assertSame(GoalType::Personal, $goal->type);
    }

    public function test_update_endpoint_fails_with_validation_error_if_the_member_id_is_sent(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $goal = Goal::factory()->forMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'Renamed',
            'member_id' => $otherMember->getKey(),
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['member_id']);
        $goal->refresh();
        $this->assertSame('Old name', $goal->name);
        $this->assertSame($data->member->getKey(), $goal->member_id);
    }

    public function test_update_endpoint_accepts_the_type_and_the_member_id_if_they_are_unchanged(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'Renamed',
            'type' => GoalType::Personal->value,
            'member_id' => $data->member->getKey(),
        ]);

        // Assert
        $response->assertStatus(200);
        $goal->refresh();
        $this->assertSame('Renamed', $goal->name);
        $this->assertSame(GoalType::Personal, $goal->type);
        $this->assertSame($data->member->getKey(), $goal->member_id);
    }

    public function test_update_endpoint_fails_with_validation_error_for_a_member_ids_filter_on_a_personal_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
            'filters' => [
                'member_ids' => [$data->member->getKey()],
            ],
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filters.member_ids']);
        $this->assertSame('Old name', $goal->refresh()->name);
    }

    public function test_update_endpoint_accepts_an_empty_member_ids_filter_on_a_personal_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
            'filters' => [
                'member_ids' => [],
            ],
        ]);

        // Assert
        $response->assertStatus(200);
        $goal->refresh();
        $this->assertSame('New name', $goal->name);
        $this->assertNull($goal->filters->memberIds);
    }

    public function test_update_endpoint_fails_for_an_organization_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->organizationGoalForMember($data->member)->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertSame('Old name', $goal->refresh()->name);
    }

    public function test_update_endpoint_fails_for_an_organization_goal_with_the_organization_type_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:organization-type']);
        $goal = Goal::factory()->forOrganization($data->organization)->forEveryMember()->create(['name' => 'Old name']);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'name' => 'New name',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertSame('Old name', $goal->refresh()->name);
    }

    public function test_update_endpoint_fails_with_validation_errors_for_invalid_body(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:update:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.goals.update', [$data->organization->getKey(), $goal->getKey()]), [
            'target_seconds' => -5,
            'period' => 'quarter',
            'is_archived' => 'maybe',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['target_seconds', 'period', 'is_archived']);
    }

    public function test_destroy_endpoint_fails_if_user_does_not_have_permission_to_delete_goals(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Goal::class, ['id' => $goal->getKey()]);
    }

    public function test_destroy_endpoint_fails_if_goal_belongs_to_other_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:delete:own']);
        $otherMember = Member::factory()->forOrganization($data->organization)->create();
        $goal = Goal::factory()->forMember($otherMember)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Goal::class, ['id' => $goal->getKey()]);
    }

    public function test_destroy_endpoint_fails_for_an_organization_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:delete:own']);
        $goal = Goal::factory()->organizationGoalForMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Goal::class, ['id' => $goal->getKey()]);
    }

    public function test_destroy_endpoint_fails_for_an_organization_goal_with_the_organization_type_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:delete:organization-type']);
        $goal = Goal::factory()->forOrganization($data->organization)->forEveryMember()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Goal::class, ['id' => $goal->getKey()]);
    }

    public function test_destroy_endpoint_fails_if_goal_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:delete:own']);
        $goal = Goal::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Goal::class, ['id' => $goal->getKey()]);
    }

    public function test_destroy_endpoint_deletes_own_goal(): void
    {
        // Arrange
        $data = $this->createUserWithPermission(['goals:delete:own']);
        $goal = Goal::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.goals.destroy', [$data->organization->getKey(), $goal->getKey()]));

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing(Goal::class, ['id' => $goal->getKey()]);
    }
}
