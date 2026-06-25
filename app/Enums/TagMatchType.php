<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum TagMatchType: string
{
    use LaravelEnumHelper;

    case Contains = 'contains';

    case NotContains = 'not_contains';
}
