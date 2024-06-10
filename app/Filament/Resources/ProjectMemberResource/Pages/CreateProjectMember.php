<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectMemberResource\Pages;

use App\Filament\Resources\ProjectMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectMember extends CreateRecord
{
    protected static string $resource = ProjectMemberResource::class;
}
