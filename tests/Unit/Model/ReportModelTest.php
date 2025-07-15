<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\Report;
use App\Service\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Report::class)]
class ReportModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $report = Report::factory()->forOrganization($organization)->create();

        // Act
        $report->refresh();
        $organizationRel = $report->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_shareable_link_is_null_when_report_is_private_but_share_secret_exists(): void
    {
        // Arrange
        $report = Report::factory()->private()->create([
            'share_secret' => app(ReportService::class)->generateSecret(),
        ]);

        // Act
        $report->refresh();

        // Assert
        $this->assertNull($report->getShareableLink());
    }

    public function test_shareable_link_is_null_when_report_is_public_but_share_secret_is_null(): void
    {
        // Arrange
        $report = Report::factory()->public()->create([
            'share_secret' => null,
        ]);

        // Act
        $report->refresh();

        // Assert
        $this->assertNull($report->getShareableLink());
    }

    public function test_shareable_link_is_null_when_report_is_public(): void
    {
        // Arrange
        $report = Report::factory()->public()->create();

        // Act
        $report->refresh();

        // Assert
        $this->assertNotNull($report->getShareableLink());
    }

    public function test_shareable_link_is_url_to_web_endpoint_when_report_is_public(): void
    {
        // Arrange
        $report = Report::factory()->public()->create();

        // Act
        $report->refresh();

        // Assert
        $this->assertSame(url('/shared-report#'.$report->share_secret), $report->getShareableLink());
    }
}
