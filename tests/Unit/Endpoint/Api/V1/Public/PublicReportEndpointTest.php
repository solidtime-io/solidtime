<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1\Public;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\Weekday;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Report;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\Dto\ReportPropertiesDto;
use Illuminate\Support\Str;
use Tests\Unit\Endpoint\Api\V1\ApiEndpointTestAbstract;

class PublicReportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_show_fails_with_not_found_if_secret_is_incorrect(): void
    {
        // Arrange
        Report::factory()->public()->create();

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => 'incorrect-secret',
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_show_fails_with_not_found_if_no_secret_is_provided(): void
    {
        // Arrange
        Report::factory()->public()->create();

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'));

        // Assert
        $response->assertNotFound();
    }

    public function test_show_fails_with_not_found_if_report_is_not_public(): void
    {
        // Arrange
        $report = Report::factory()->private()->create();

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_show_fails_with_not_found_if_report_is_expired(): void
    {
        // Arrange
        $report = Report::factory()->public()->create([
            'public_until' => now()->subDay(),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_show_returns_detailed_information_about_the_report(): void
    {
        // Arrange
        $reportDto = new ReportPropertiesDto;
        $organization = Organization::factory()->create();
        $reportDto->start = now()->subDays(2);
        $reportDto->end = now();
        $reportDto->group = TimeEntryAggregationType::Project;
        $reportDto->subGroup = TimeEntryAggregationType::Task;
        $reportDto->historyGroup = TimeEntryAggregationTypeInterval::Day;
        $reportDto->weekStart = Weekday::Monday;
        $reportDto->timezone = 'Europe/Vienna';
        $report = Report::factory()->forOrganization($organization)->public()->create([
            'public_until' => null,
            'properties' => $reportDto,
        ]);
        $project = Project::factory()->forOrganization($organization)->create();
        $task1 = Task::factory()->forOrganization($organization)->forProject($project)->create([
            'id' => '1b0f1b32-0def-4932-8829-b68f52161987',
        ]);
        $task2 = Task::factory()->forOrganization($organization)->forProject($project)->create([
            'id' => '3c54796d-5ab4-41e1-8f30-aa61a0a919ae',
        ]);
        TimeEntry::factory()->forOrganization($organization)->forTask($task1)->startWithDuration(now()->subDay(), 100)->create();
        TimeEntry::factory()->forOrganization($organization)->forTask($task2)->startWithDuration(now()->subDay(), 100)->create();
        TimeEntry::factory()->forOrganization($organization)->startWithDuration(now()->subDay(), 100)->create();

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'name' => $report->name,
            'description' => $report->description,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
            'currency' => $organization->currency,
            'properties' => [
                'group' => $reportDto->group->value,
                'sub_group' => $reportDto->subGroup->value,
                'history_group' => $reportDto->historyGroup->value,
                'start' => $reportDto->start->toIso8601ZuluString(),
                'end' => $reportDto->end->toIso8601ZuluString(),
            ],
            'data' => [
                'seconds' => 300,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationType::Project->value,
                'grouped_data' => [
                    [
                        'key' => $project->id,
                        'seconds' => 200,
                        'cost' => 0,
                        'grouped_type' => TimeEntryAggregationType::Task->value,
                        'grouped_data' => [
                            [
                                'key' => $task1->id,
                                'seconds' => 100,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                                'description' => $task1->name,
                                'color' => null,
                            ],
                            [
                                'key' => $task2->id,
                                'seconds' => 100,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                                'description' => $task2->name,
                                'color' => null,
                            ],
                        ],
                        'description' => $project->name,
                        'color' => $project->color,
                    ],
                    [
                        'key' => null,
                        'seconds' => 100,
                        'cost' => 0,
                        'grouped_type' => TimeEntryAggregationType::Task->value,
                        'grouped_data' => [
                            [
                                'key' => null,
                                'seconds' => 100,
                                'cost' => 0,
                                'grouped_type' => null,
                                'grouped_data' => null,
                                'description' => null,
                                'color' => null,
                            ],
                        ],
                        'description' => null,
                        'color' => null,
                    ],
                ],
            ],
            'history_data' => [
                'seconds' => 300,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationTypeInterval::Day->value,
                'grouped_data' => [
                    [
                        'key' => now()->subDays(2)->toDateString(),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                    [
                        'key' => now()->subDays(1)->toDateString(),
                        'seconds' => 300,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                    [
                        'key' => now()->toDateString(),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                ],
            ],
        ]);
    }

    public function test_show_returns_detailed_information_about_the_report_with_not_expired_expiration_date(): void
    {
        // Arrange
        $report = Report::factory()->public()->create([
            'public_until' => now()->addDay(),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $report->name,
            'description' => $report->description,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
        ]);
    }

    public function test_show_returns_detailed_information_about_the_report_with_all_available_filters(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($organization)->create();
        $otherClient = Client::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forClient($client)->forOrganization($organization)->create();
        $otherProject = Project::factory()->forOrganization($organization)->create();
        $otherProjectWithClient = Project::factory()->forClient($client)->forOrganization($organization)->create();
        $task = Task::factory()->forOrganization($organization)->forProject($project)->create();
        $tag = Tag::factory()->forOrganization($organization)->create();
        $otherTag = Tag::factory()->forOrganization($organization)->create();

        // Match for all filters
        TimeEntry::factory()->forOrganization($organization)
            ->forTask($task)
            ->billable()
            ->startWithDuration(now()->subDay(), 100)
            ->create([
                'tags' => [$tag->getKey()],
            ]);
        // No match for task filter
        TimeEntry::factory()->forOrganization($organization)
            ->forProject($otherProject)
            ->startWithDuration(now()->subDay(), 100)
            ->create();
        // No match for client filter
        TimeEntry::factory()->forOrganization($organization)
            ->forProject($otherProjectWithClient)
            ->startWithDuration(now()->subDay(), 100)
            ->create();

        $reportDto = new ReportPropertiesDto;
        $reportDto->start = now()->subDays(2);
        $reportDto->end = now();
        $reportDto->group = TimeEntryAggregationType::Project;
        $reportDto->subGroup = TimeEntryAggregationType::Task;
        $reportDto->historyGroup = TimeEntryAggregationTypeInterval::Day;
        $reportDto->weekStart = Weekday::Monday;
        $reportDto->timezone = 'Europe/Vienna';
        $reportDto->active = false;
        $reportDto->billable = true;
        $reportDto->setMemberIds(null);
        $reportDto->setClientIds([$client->getKey()]);
        $reportDto->setProjectIds([$project->getKey()]);
        $reportDto->setTagIds([$tag->getKey()]);
        $reportDto->setTaskIds([$task->getKey()]);
        $report = Report::factory()->forOrganization($organization)->public()->create([
            'public_until' => null,
            'properties' => $reportDto,
        ]);

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'name' => $report->name,
            'description' => $report->description,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
            'properties' => [
                'group' => $reportDto->group->value,
                'sub_group' => $reportDto->subGroup->value,
                'history_group' => $reportDto->historyGroup->value,
                'start' => $reportDto->start->toIso8601ZuluString(),
                'end' => $reportDto->end->toIso8601ZuluString(),
            ],
            'data' => [
                'seconds' => 100,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationType::Project->value,
            ],
            'history_data' => [
                'seconds' => 100,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationTypeInterval::Day->value,
            ],
        ]);
    }

    public function test_if_the_resources_behind_the_filters_no_longer_exist_the_report_ignores_those_filters_but_this_does_not_increase_the_visible_data(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forClient($client)->forOrganization($organization)->create();
        $task = Task::factory()->forOrganization($organization)->forProject($project)->create();
        $tag = Tag::factory()->forOrganization($organization)->create();

        TimeEntry::factory()->forOrganization($organization)
            ->forTask($task)
            ->billable()
            ->startWithDuration(now()->subDay(), 100)
            ->create([
                'tags' => [$tag->getKey()],
            ]);

        $reportDto = new ReportPropertiesDto;
        $reportDto->start = now()->subDays(2);
        $reportDto->end = now();
        $reportDto->group = TimeEntryAggregationType::Project;
        $reportDto->subGroup = TimeEntryAggregationType::Task;
        $reportDto->historyGroup = TimeEntryAggregationTypeInterval::Day;
        $reportDto->weekStart = Weekday::Monday;
        $reportDto->timezone = 'Europe/Vienna';
        $reportDto->setMemberIds([Str::uuid()->toString()]);
        $reportDto->setClientIds([Str::uuid()->toString()]);
        $reportDto->setProjectIds([Str::uuid()->toString()]);
        $reportDto->setTagIds([Str::uuid()->toString()]);
        $reportDto->setTaskIds([Str::uuid()->toString()]);
        $report = Report::factory()->forOrganization($organization)->public()->create([
            'public_until' => null,
            'properties' => $reportDto,
        ]);

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson([
            'name' => $report->name,
            'description' => $report->description,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
            'properties' => [
                'group' => $reportDto->group->value,
                'sub_group' => $reportDto->subGroup->value,
                'history_group' => $reportDto->historyGroup->value,
                'start' => $reportDto->start->toIso8601ZuluString(),
                'end' => $reportDto->end->toIso8601ZuluString(),
            ],
            'data' => [
                'seconds' => 0,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationType::Project->value,
                'grouped_data' => [],
            ],
            'history_data' => [
                'seconds' => 0,
                'cost' => 0,
                'grouped_type' => TimeEntryAggregationTypeInterval::Day->value,
                'grouped_data' => [
                    [
                        'key' => now()->subDays(2)->toDateString(),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                    [
                        'key' => now()->subDays(1)->toDateString(),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                    [
                        'key' => now()->toDateString(),
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => null,
                        'grouped_data' => null,
                        'description' => null,
                        'color' => null,
                    ],
                ],
            ],
        ]);
    }
}
