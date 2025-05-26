<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

/**
 * @info https://en.wikipedia.org/wiki/Decimal_separator
 */
enum NumberFormat: string
{
    use LaravelEnumHelper;

    case ThousandsPointDecimalComma = 'point-comma';

    case ThousandsCommaDecimalPoint = 'comma-point';
    case ThousandsSpaceDecimalComma = 'space-comma';

    case ThousandsSpaceDecimalPoint = 'space-point';

    case ThousandsApostropheDecimalPoint = 'apostrophe-point';

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $selectArray = [];
        foreach (self::values() as $value) {
            $selectArray[(string) $value] = (string) __('enum.number_format.'.$value);
        }

        return $selectArray;
    }
}
