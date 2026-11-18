<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExportResource\Pages;

use App\Filament\Resources\ExportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewExport extends ViewRecord
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
