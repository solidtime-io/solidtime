<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Models\User;

class FilamentActionsEndpointTest extends EndpointTestAbstract
{
    public function test_export_download_returns_not_found(): void
    {
        // Act
        $response = $this->get('/filament/exports/1/download');

        // Assert
        $response->assertNotFound();
    }

    public function test_import_failed_rows_download_returns_not_found(): void
    {
        // Act
        $response = $this->get('/filament/imports/1/failed-rows/download');

        // Assert
        $response->assertNotFound();
    }

    public function test_import_failed_rows_download_returns_not_found_for_logged_in_user(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/filament/imports/1/failed-rows/download');

        // Assert
        $response->assertNotFound();
    }
}
