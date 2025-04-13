<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum TimeFormat: string
{
    use LaravelEnumHelper;

    case TwelveHours = '12-hours';
    case TwentyFourHours = '24-hours';

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $selectArray = [];
        foreach (self::values() as $value) {
            $selectArray[(string) $value] = (string) __('enum.time_format.'.$value);
        }

        return $selectArray;
    }
}
