<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum IntervalFormat: string
{
    use LaravelEnumHelper;

    case Decimal = 'decimal';
    case HoursMinutes = 'hours-minutes';

    case HoursMinutesColonSeparated = 'hours-minutes-colon-separated';

    case HoursMinutesSecondsColonSeparated = 'hours-minutes-seconds-colon-separated';

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $selectArray = [];
        foreach (self::values() as $value) {
            $selectArray[(string) $value] = (string) __('enum.interval_format.'.$value);
        }

        return $selectArray;
    }
}
