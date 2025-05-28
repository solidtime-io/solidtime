<?php

declare(strict_types=1);

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\Weekday;

return [

    'weekday' => [
        Weekday::Monday->value => 'Pazartesi',
        Weekday::Tuesday->value => 'Salı',
        Weekday::Wednesday->value => 'Çarşamba',
        Weekday::Thursday->value => 'Perşembe',
        Weekday::Friday->value => 'Cuma',
        Weekday::Saturday->value => 'Cumartesi',
        Weekday::Sunday->value => 'Pazar',
    ],

    'number_format' => [
        NumberFormat::ThousandsPointDecimalComma->value => '1.111,11',
        NumberFormat::ThousandsCommaDecimalPoint->value => '1,111.11',
        NumberFormat::ThousandsSpaceDecimalComma->value => '1 111,11',
        NumberFormat::ThousandsSpaceDecimalPoint->value => '1 111.11',
        NumberFormat::ThousandsApostropheDecimalPoint->value => '1\'111.11',
    ],

    'date_format' => [
        DateFormat::PointSeparatedDMYYYY->value => 'G.A.YYYY',
        DateFormat::SlashSeparatedMMDDYYYY->value => 'AA/GG/YYYY',
        DateFormat::SlashSeparatedDDMMYYYY->value => 'GG/AA/YYYY',
        DateFormat::HyphenSeparatedDDMMYYY->value => 'GG-AA-YYYY',
        DateFormat::HyphenSeparatedMMDDDYYYY->value => 'AA-GG-YYYY',
        DateFormat::HyphenSeparatedYYYYMMDD->value => 'YYYY-AA-GG',
    ],

    'time_format' => [
        TimeFormat::TwelveHours->value => '12 saatlik format',
        TimeFormat::TwentyFourHours->value => '24 saatlik format',
    ],

    'interval_format' => [
        IntervalFormat::Decimal->value => 'Ondalık',
        IntervalFormat::HoursMinutes->value => '12s 3d',
        IntervalFormat::HoursMinutesColonSeparated->value => '12:03',
        IntervalFormat::HoursMinutesSecondsColonSeparated->value => '12:03:45',
    ],

    'currency_format' => [
        CurrencyFormat::ISOCodeBeforeWithSpace->value => 'EUR 111',
        CurrencyFormat::ISOCodeAfterWithSpace->value => '111 EUR',
        CurrencyFormat::SymbolBefore->value => '€111',
        CurrencyFormat::SymbolAfter->value => '111€',
        CurrencyFormat::SymbolBeforeWithSpace->value => '€ 111',
        CurrencyFormat::SymbolAfterWithSpace->value => '111 €',
    ],

];