<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ExportController;
use App\Models\Organization;
use App\Service\Export\ExportException;
use App\Service\Export\ExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ExportController::class)]
class ExportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_export_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $this->mock(ExportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('export');
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_export_return_error_message_if_export_fails(): void
    {
        $user = $this->createUserWithPermission([
            'export',
        ]);
        $this->mock(ExportService::class, function (MockInterface $mock) use (&$user): void {
            $mock->shouldReceive('export')
                ->withArgs(function (Organization $organization) use (&$user): bool {
                    return $organization->is($user->organization);
                })
                ->andThrow(new ExportException())
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', ['organization' => $user->organization->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'export',
            'message' => 'Export failed, please try again later or contact support',
        ]);
    }

    public function test_export_calls_export_service_if_user_has_permission(): void
    {
        // Arrange
        $user = $this->createUserWithPermission([
            'export',
        ]);
        $filepath = 'exports/path.zip';
        Storage::fake('local');
        $now = Carbon::now();
        $this->travelTo($now);
        $this->mock(ExportService::class, function (MockInterface $mock) use (&$user, $filepath): void {
            $mock->shouldReceive('export')
                ->withArgs(function (Organization $organization) use (&$user): bool {
                    return $organization->is($user->organization);
                })
                ->andReturn($filepath)
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.export.export', [
            'organization' => $user->organization->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertStringContainsString($filepath, $response->json('download_url'));
    }
}
