<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum TimeEntryType: string
{
    use LaravelEnumHelper;

    case Work = 'work';
    case Break = 'break';
}
