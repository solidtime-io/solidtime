<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Manager = 'manager';
    case Normal = 'normal';
}
