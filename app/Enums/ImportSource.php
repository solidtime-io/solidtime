<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum ImportSource: string
{
    use LaravelEnumHelper;

    /**
     * Import of an uploaded file (CSV, JSON or ZIP)
     */
    case File = 'file';

    /**
     * Import via the API of a third party service
     */
    case Api = 'api';
}
