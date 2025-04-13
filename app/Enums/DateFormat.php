<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum DateFormat: string
{
    use LaravelEnumHelper;

    case PointSeperatedDMYYYY = 'point-seperated-d-m-yyyy';
    case SlashSeperatedMMDDYYYY = 'slash-seperated-mm-dd-yyyy';

    case SlashSeperatedDDMMYYYY = 'slash-seperated-dd-mm-yyyy';

    case HyphenSeperatedDDMMYYY = 'hyphen-seperated-dd-mm-yyyy';

    case HyphenSeperatedMMDDDYYYY = 'hyphen-seperated-mm-dd-yyyy';

    case HyphenSeperatedYYYYMMDD = 'hyphen-seperated-yyyy-mm-dd';

    public function toCarbonFormat(): string
    {
        return match ($this->value) {
            self::PointSeperatedDMYYYY->value => 'j.n.Y',
            self::SlashSeperatedMMDDYYYY->value => 'm/d/Y',
            self::SlashSeperatedDDMMYYYY->value => 'd/m/Y',
            self::HyphenSeperatedDDMMYYY->value => 'd-m-Y',
            self::HyphenSeperatedMMDDDYYYY->value => 'm-d-Y',
            self::HyphenSeperatedYYYYMMDD->value => 'Y-m-d',
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
