<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;
}
