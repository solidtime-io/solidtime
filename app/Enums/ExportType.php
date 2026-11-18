<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum ExportType: string
{
    use LaravelEnumHelper;

    /**
     * Full export of all data of an organization (ZIP with CSV files), can be imported with the solidtime importer
     */
    case Organization = 'organization';
}
