<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum DateFormat: string
{
    use LaravelEnumHelper;

    case PointSeparatedDMYYYY = 'point-separated-d-m-yyyy';
    case SlashSeparatedMMDDYYYY = 'slash-separated-mm-dd-yyyy';

    case SlashSeparatedDDMMYYYY = 'slash-separated-dd-mm-yyyy';

    case HyphenSeparatedDDMMYYY = 'hyphen-separated-dd-mm-yyyy';

    case HyphenSeparatedMMDDDYYYY = 'hyphen-separated-mm-dd-yyyy';

    case HyphenSeparatedYYYYMMDD = 'hyphen-separated-yyyy-mm-dd';

    public function toCarbonFormat(): string
    {
        return match ($this->value) {
            self::PointSeparatedDMYYYY->value => 'j.n.Y',
            self::SlashSeparatedMMDDYYYY->value => 'm/d/Y',
            self::SlashSeparatedDDMMYYYY->value => 'd/m/Y',
            self::HyphenSeparatedDDMMYYY->value => 'd-m-Y',
            self::HyphenSeparatedMMDDDYYYY->value => 'm-d-Y',
            self::HyphenSeparatedYYYYMMDD->value => 'Y-m-d',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $selectArray = [];
        foreach (self::values() as $value) {
            $selectArray[(string) $value] = (string) __('enum.date_format.'.$value);
        }

        return $selectArray;
    }
}
