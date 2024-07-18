<?php

declare(strict_types=1);

namespace App\Filament\Resources\FailedJobResource\Pages;

use App\Filament\Resources\FailedJobResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFailedJobs extends ViewRecord
{
    protected static string $resource = FailedJobResource::class;
}
