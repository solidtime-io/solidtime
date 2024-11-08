<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;
use Illuminate\Support\Carbon;

enum Weekday: string
{
    use LaravelEnumHelper;

    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function toEndOfWeek(): self
    {
        return match ($this) {
            Weekday::Monday => Weekday::Sunday,
            Weekday::Tuesday => Weekday::Monday,
            Weekday::Wednesday => Weekday::Tuesday,
            Weekday::Thursday => Weekday::Wednesday,
            Weekday::Friday => Weekday::Thursday,
            Weekday::Saturday => Weekday::Friday,
            Weekday::Sunday => Weekday::Saturday,
        };
    }

    public function carbonWeekDay(): int
    {
        return match ($this) {
            Weekday::Monday => Carbon::MONDAY,
            Weekday::Tuesday => Carbon::TUESDAY,
            Weekday::Wednesday => Carbon::WEDNESDAY,
            Weekday::Thursday => Carbon::THURSDAY,
            Weekday::Friday => Carbon::FRIDAY,
            Weekday::Saturday => Carbon::SATURDAY,
            Weekday::Sunday => Carbon::SUNDAY,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        return [
            Weekday::Monday->value => __('enum.weekday.'.Weekday::Monday->value),
            Weekday::Tuesday->value => __('enum.weekday.'.Weekday::Tuesday->value),
            Weekday::Wednesday->value => __('enum.weekday.'.Weekday::Wednesday->value),
            Weekday::Thursday->value => __('enum.weekday.'.Weekday::Thursday->value),
            Weekday::Friday->value => __('enum.weekday.'.Weekday::Friday->value),
            Weekday::Saturday->value => __('enum.weekday.'.Weekday::Saturday->value),
            Weekday::Sunday->value => __('enum.weekday.'.Weekday::Sunday->value),
        ];
    }
}
