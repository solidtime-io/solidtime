<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum ExportStatus: string
{
    use LaravelEnumHelper;

    case Pending = 'pending';

    case Running = 'running';

    case Completed = 'completed';

    case Failed = 'failed';

    /**
     * Statuses of exports that are not finished yet, only one export per organization can have one of these statuses.
     *
     * @return array<ExportStatus>
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
