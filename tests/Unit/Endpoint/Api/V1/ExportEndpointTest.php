<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\ExportStatus;
use App\Http\Controllers\Api\V1\ExportController;
use App\Jobs\ProcessExport;
use App\Models\Export;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ExportController::class)]
class ExportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_export_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Queue::fake();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertSame(0, Export::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_export_creates_pending_export_and_dispatches_job(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        Queue::fake();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertStatus(202);
        /** @var Export $export */
        $export = Export::query()->sole();
        $this->assertSame(ExportStatus::Pending, $export->status);
        $this->assertSame($data->organization->getKey(), $export->organization_id);
        $this->assertSame($data->user->getKey(), $export->user_id);
        $response->assertJsonPath('data.id', $export->getKey());
        $response->assertJsonPath('data.type', 'organization');
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.download_available', false);
        $response->assertJsonPath('data.error_message', null);
        $response->assertJsonPath('data.user_id', $data->user->getKey());
        Queue::assertPushed(ProcessExport::class, fn (ProcessExport $job): bool => $job->export->is($export));
    }

    public function test_export_fails_if_export_of_organization_is_already_in_progress(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        Export::factory()->forOrganization($data->organization)->running()->create();
        Queue::fake();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'export_already_in_progress',
            'message' => 'An export of this organization is already in progress',
        ]);
        $this->assertSame(1, Export::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_index_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.index', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_returns_exports_of_organization_newest_first(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $older = Export::factory()->forOrganization($data->organization)->completed()->create([
            'created_at' => Carbon::now()->subDay(),
        ]);
        $newer = Export::factory()->forOrganization($data->organization)->failed()->create([
            'created_at' => Carbon::now(),
        ]);
        Export::factory()->forOrganization(Organization::factory()->create())->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.index', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->getKey());
        $response->assertJsonPath('data.0.status', 'failed');
        $response->assertJsonPath('data.0.error_message', 'Export failed');
        $response->assertJsonPath('data.0.download_available', false);
        $response->assertJsonPath('data.1.id', $older->getKey());
        $response->assertJsonPath('data.1.download_available', true);
        $response->assertJsonMissingPath('data.0.file_path');
        $response->assertJsonMissingPath('data.0.error_details');
    }

    public function test_show_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $export = Export::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.show', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_fails_if_export_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $export = Export::factory()->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.show', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_returns_export(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $export = Export::factory()->forOrganization($data->organization)->running()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.show', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.id', $export->getKey());
        $response->assertJsonPath('data.status', 'running');
        $response->assertJsonPath('data.download_available', false);
    }

    public function test_download_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $export = Export::factory()->forOrganization($data->organization)->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.download', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_download_fails_if_export_belongs_to_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $export = Export::factory()->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.download', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_download_fails_if_export_is_not_completed(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $export = Export::factory()->forOrganization($data->organization)->running()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.download', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('key', 'export_file_not_available');
    }

    public function test_download_fails_if_export_file_was_pruned(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $export = Export::factory()->forOrganization($data->organization)->completed()->create([
            'file_path' => null,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.export.exports.download', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('key', 'export_file_not_available');
    }

    public function test_download_returns_temporary_url_as_attachment_with_readable_filename(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'export',
        ]);
        $data->organization->name = 'Acme GmbH';
        $data->organization->save();
        $data->user->timezone = 'Europe/Vienna';
        $data->user->save();
        $export = Export::factory()->forOrganization($data->organization)->completed()->create([
            'id' => '1f2470b2-19d9-4d45-8fc3-eb5c1e6040e6',
            'file_path' => 'exports/test/export.zip',
            'created_at' => Carbon::parse('2026-11-18 10:20:30', 'UTC'),
        ]);
        Passport::actingAs($data->user);
        $capturedOptions = $this->captureTemporaryUrlOptions();

        // Act
        $response = $this->getJson(route('api.v1.export.exports.download', [
            'organization' => $data->organization->getKey(),
            'export' => $export->getKey(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'download_url' => 'https://storage.fake/exports/test/export.zip',
        ]);
        $options = $capturedOptions();
        $this->assertIsArray($options);
        $this->assertSame(
            'attachment; filename="solidtime-export_acme-gmbh_2026-11-18_11-20_1f2470b2.zip"',
            $options['ResponseContentDisposition'] ?? null
        );
    }
}
