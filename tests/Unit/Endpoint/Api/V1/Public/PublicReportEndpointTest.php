<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1\Public;

use App\Models\Report;
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
        $report = Report::factory()->public()->create([
            'public_until' => null,
        ]);

        // Act
        $response = $this->getJson(route('api.v1.public.reports.show'), [
            'X-Api-Key' => $report->share_secret,
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $report->id,
            'name' => $report->name,
            'description' => $report->description,
            'is_public' => $report->is_public,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
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
            'id' => $report->id,
            'name' => $report->name,
            'description' => $report->description,
            'is_public' => $report->is_public,
            'public_until' => $report->public_until?->toIso8601ZuluString(),
        ]);
    }
}
