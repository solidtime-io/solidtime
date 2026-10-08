<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\ExportFormat;
use App\Service\ReportService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ReportService::class)]
class ReportServiceTest extends TestCase
{
    public function test_export_filename_contains_report_name_and_date_range(): void
    {
        // Arrange
        $reportService = app(ReportService::class);
        $start = Carbon::parse('2026-07-01 00:00:00');
        $end = Carbon::parse('2026-07-31 23:59:59');

        // Act
        $result = $reportService->exportFilename('Client "Acme" / July', $start, $end, ExportFormat::XLSX);

        // Assert
        $this->assertSame('solidtime-client-acme-july-2026-07-01-to-2026-07-31.xlsx', $result);
    }

    public function test_export_filename_falls_back_to_report_if_name_has_no_ascii_representation(): void
    {
        // Arrange
        $reportService = app(ReportService::class);
        $start = Carbon::parse('2026-07-01 00:00:00');
        $end = Carbon::parse('2026-07-31 23:59:59');

        // Act
        $result = $reportService->exportFilename('😀', $start, $end, ExportFormat::PDF);

        // Assert
        $this->assertSame('solidtime-report-2026-07-01-to-2026-07-31.pdf', $result);
    }
}
