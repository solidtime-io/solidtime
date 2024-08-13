<?php

declare(strict_types=1);

namespace App\Service;

use Illuminate\Support\Str;

class ReportService
{
    public function generateSecret(): string
    {
        return Str::random(40);
    }
}
