<?php

declare(strict_types=1);

namespace App\Enums;

use Maatwebsite\Excel\Excel;

enum ExportFormat: string
{
    case CSV = 'csv';
    case PDF = 'pdf';
    case XLSX = 'xlsx';
    case ODS = 'ods';

    public function getFileExtension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::PDF => 'pdf',
            self::XLSX => 'xlsx',
            self::ODS => 'ods',
        };
    }

    public function getExportPackageType(): string
    {
        return match ($this) {
            self::CSV => Excel::CSV,
            self::PDF => Excel::MPDF,
            self::XLSX => Excel::XLSX,
            self::ODS => Excel::ODS,
        };
    }
}
