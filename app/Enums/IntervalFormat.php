<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum IntervalFormat: string
{
    use LaravelEnumHelper;

    case Decimal = 'decimal';
    case HoursMinutes = 'hours-minutes';

    case HoursMinutesColonSeperated = 'hours-minutes-colon-seperated';

    case HoursMinutesSecondsColonSeperated = 'hours-minutes-seconds-colon-seperated';

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
