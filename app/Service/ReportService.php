<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\ExportFormat;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ReportService
{
    public function generateSecret(): string
    {
        return Str::random(40);
    }

    public function exportFilename(string $reportName, CarbonInterface $start, CarbonInterface $end, ExportFormat $format): string
    {
        $slug = Str::slug($reportName);

        return 'solidtime-'.($slug !== '' ? $slug : 'report').'-'.$start->format('Y-m-d').'-to-'.$end->format('Y-m-d').'.'.$format->getFileExtension();
    }
}
