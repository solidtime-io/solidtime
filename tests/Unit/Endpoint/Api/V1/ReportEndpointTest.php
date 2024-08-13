<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\TimeEntryAggregationType;
use App\Http\Controllers\Api\V1\ReportController;
use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ReportController::class)]
class ReportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_does_not_have_permission_to_view_reports(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Report::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.index', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_reports_of_organization_ordered_by_created_at_desc_per_default(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:view',
        ]);
        Report::factory()->forOrganization($data->organization)->randomCreatedAt()->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
        $reports = Report::query()->orderBy('created_at', 'desc')->get();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->has('links')
            ->has('meta')
            ->count('data', 4)
            ->where('data.0.id', $reports->get(0)->getKey())
            ->where('data.1.id', $reports->get(1)->getKey())
            ->where('data.2.id', $reports->get(2)->getKey())
            ->where('data.3.id', $reports->get(3)->getKey())
        );
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.reports.store', [$data->organization->getKey()]), [
            'name' => 'Test Report',
            'is_public' => false,
            'properties' => [
                'group' => TimeEntryAggregationType::Project->value,
                'sub_group' => TimeEntryAggregationType::Task->value,
            ],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_new_report_with_minimal_properties(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.reports.store', [$data->organization->getKey()]), [
            'name' => 'Test Report',
            'is_public' => false,
            'properties' => [
                'group' => TimeEntryAggregationType::Project->value,
                'sub_group' => TimeEntryAggregationType::Task->value,
            ],
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Test Report')
            ->where('data.description', null)
            ->where('data.is_public', false)
            ->where('data.shareable_link', null)
            ->where('data.properties.group', TimeEntryAggregationType::Project->value)
            ->where('data.properties.sub_group', TimeEntryAggregationType::Task->value)
        );
    }

    public function test_store_endpoint_creates_new_report_with_all_properties(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.reports.store', [$data->organization->getKey()]), [
            'name' => 'Test Report',
            'description' => 'Test description',
            'is_public' => true,
            'public_until' => Carbon::now()->addDays(30)->toIso8601ZuluString(),
            'properties' => [
                'start' => Carbon::now()->subDays(30)->toIso8601ZuluString(),
                'end' => Carbon::now()->toIso8601ZuluString(),
                'active' => true,
                'member_ids' => [],
                'billable' => true,
                'client_ids' => [],
                'project_ids' => [],
                'tag_ids' => [],
                'task_ids' => [],
                'group' => TimeEntryAggregationType::Project->value,
                'sub_group' => TimeEntryAggregationType::Task->value,
            ],
        ]);

        // Assert
        $response->assertStatus(201);
        /** @var Report $report */
        $report = Report::query()->findOrFail($response->json('data.id'));
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Test Report')
            ->where('data.description', 'Test description')
            ->where('data.is_public', true)
            ->where('data.shareable_link', $report->getShareableLink())
            ->where('data.properties.group', TimeEntryAggregationType::Project->value)
            ->where('data.properties.sub_group', TimeEntryAggregationType::Task->value)
        );
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'name' => 'Updated Report',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_report_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), 1]), [
            'name' => 'Updated Report',
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_update_endpoint_fails_if_report_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'name' => 'Updated Report',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_can_update_only_the_name_of_the_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'name' => 'Updated Report',
        ]);

        // Assert
        $report->refresh();
        $this->assertSame('Updated Report', $report->name);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Updated Report')
        );
    }

    public function test_update_endpoint_can_update_only_the_description_of_the_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'description' => 'Updated description',
        ]);

        // Assert
        $report->refresh();
        $this->assertSame('Updated description', $report->description);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.description', 'Updated description')
        );
    }

    public function test_update_endpoint_can_set_a_report_to_public_which_generates_a_new_secret(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->private()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'is_public' => true,
        ]);

        // Assert
        $report->refresh();
        $this->assertTrue($report->is_public);
        $this->assertNotNull($report->share_secret);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.is_public', true)
            ->where('data.shareable_link', $report->getShareableLink())
        );
    }

    public function test_update_endpoint_can_set_a_report_to_private_which_resets_the_secret(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->public()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'is_public' => false,
        ]);

        // Assert
        $report->refresh();
        $this->assertFalse($report->is_public);
        $this->assertNull($report->share_secret);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.is_public', false)
            ->where('data.shareable_link', null)
        );
    }

    public function test_update_endpoint_does_not_change_the_secret_of_a_public_report_if_it_is_set_to_public_again(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->public()->forOrganization($data->organization)->create();
        $secret = $report->share_secret;
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'is_public' => true,
        ]);

        // Assert
        $report->refresh();
        $this->assertTrue($report->is_public);
        $this->assertSame($secret, $report->share_secret);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.is_public', true)
            ->where('data.shareable_link', $report->getShareableLink())
        );
    }

    public function test_update_endpoint_can_update_the_report_all_properties_set(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:update',
        ]);
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.reports.update', [$data->organization->getKey(), $report->getKey()]), [
            'name' => 'Updated Report',
            'description' => 'Updated description',
            'is_public' => true,
            'public_until' => Carbon::now()->addDays(30)->toIso8601ZuluString(),
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Updated Report')
            ->where('data.description', 'Updated description')
            ->where('data.is_public', true)
            ->where('data.properties.group', TimeEntryAggregationType::Project->value)
            ->where('data.properties.sub_group', TimeEntryAggregationType::Task->value)
        );
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.show', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_if_report_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.show', [$data->organization->getKey(), 1]));

        // Assert
        $response->assertNotFound();
    }

    public function test_show_endpoint_fails_if_report_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:view',
        ]);
        $report = Report::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.show', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_detailed_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:view',
        ]);
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.reports.show', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.id', $report->getKey())
        );
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.reports.destroy', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_report_belongs_to_another_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:delete',
        ]);
        $report = Report::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.reports.destroy', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_report_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.reports.destroy', [$data->organization->getKey(), 1]));

        // Assert
        $response->assertNotFound();
    }

    public function test_destroy_endpoint_deletes_a_report(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'reports:delete',
        ]);
        $report = Report::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.reports.destroy', [$data->organization->getKey(), $report->getKey()]));

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing(Report::class, [
            'id' => $report->getKey(),
        ]);
    }
}
