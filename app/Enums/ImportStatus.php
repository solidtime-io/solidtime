<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum ImportStatus: string
{
    use LaravelEnumHelper;

    case Pending = 'pending';

    case Running = 'running';

    case Completed = 'completed';

    case Failed = 'failed';

    /**
     * Statuses of imports that are not finished yet, only one import per organization can have one of these statuses.
     *
     * @return array<ImportStatus>
     */
    public static function active(): array
    {
        return [self::Pending, self::Running];
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
