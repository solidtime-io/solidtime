<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Models\Organization;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

class LocalizationService
{
    private CurrencyFormat $currencyFormat;

    private IntervalFormat $intervalFormat;

    private DateFormat $dateFormat;

    private TimeFormat $timeFormat;

    private NumberFormat $numberFormat;

    public function __construct(CurrencyFormat $currencyFormat, DateFormat $dateFormat, TimeFormat $timeFormat, NumberFormat $numberFormat, IntervalFormat $intervalFormat)
    {
        $this->currencyFormat = $currencyFormat;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->numberFormat = $numberFormat;
        $this->intervalFormat = $intervalFormat;
    }

    public static function forOrganization(Organization $organization): self
    {
        return new LocalizationService(
            $organization->currency_format,
            $organization->date_format,
            $organization->time_format,
            $organization->number_format,
            $organization->interval_format
        );
    }

    public function formatNumber(BigDecimal|float $number): string
    {
        $numberFloat = $number instanceof BigDecimal ? $number->toFloat() : $number;

        if ($this->numberFormat === NumberFormat::ThousandsPointDecimalComma) {
            return number_format($numberFloat, 2, ',', '.');
        } elseif ($this->numberFormat === NumberFormat::ThousandsSpaceDecimalPoint) {
            return number_format($numberFloat, 2, '.', ' ');
        } elseif ($this->numberFormat === NumberFormat::ThousandsCommaDecimalPoint) {
            return number_format($numberFloat, 2, '.', ',');
        } elseif ($this->numberFormat === NumberFormat::ThousandsSpaceDecimalComma) {
            return number_format($numberFloat, 2, ',', ' ');
        } elseif ($this->numberFormat === NumberFormat::ThousandsApostropheDecimalPoint) {
            return number_format($numberFloat, 2, '.', '\'');
        }
    }

    public function formatNumberWithoutTrailingZeros(BigDecimal|float $number): string
    {
        $number = $this->formatNumber($number);

        $number = rtrim($number, '0');
        $number = rtrim($number, '.');
        $number = rtrim($number, ',');

        return $number;
    }

    public function formatInterval(CarbonInterval $interval): string
    {
        if ($this->intervalFormat === IntervalFormat::Decimal) {
            $interval->cascade();

            return $this->formatNumber($interval->totalHours).' h';
        } elseif ($this->intervalFormat === IntervalFormat::HoursMinutes) {
            $interval->cascade();

            return ((int) floor($interval->totalHours)).'h '.$interval->format('%I').'m';
        } elseif ($this->intervalFormat === IntervalFormat::HoursMinutesColonSeparated) {
            $interval->cascade();

            return ((int) floor($interval->totalHours)).':'.$interval->format('%I');
        } elseif ($this->intervalFormat === IntervalFormat::HoursMinutesSecondsColonSeparated) {
            $interval->cascade();

            return ((int) floor($interval->totalHours)).':'.$interval->format('%I:%S');
        }
    }

    public function formatCurrency(Money $money): string
    {
        $currencyService = app(CurrencyService::class);
        if ($this->currencyFormat === CurrencyFormat::ISOCodeAfterWithSpace) {
            return $this->formatNumber($money->getAmount()).' '.$money->getCurrency()->getCurrencyCode();
        } elseif ($this->currencyFormat === CurrencyFormat::ISOCodeBeforeWithSpace) {
            return $money->getCurrency()->getCurrencyCode().' '.$this->formatNumber($money->getAmount());
        } elseif ($this->currencyFormat === CurrencyFormat::SymbolAfter) {
            return $this->formatNumber($money->getAmount()).$currencyService->getCurrencySymbolForMoney($money);
        } elseif ($this->currencyFormat === CurrencyFormat::SymbolBefore) {
            return $currencyService->getCurrencySymbolForMoney($money).$this->formatNumber($money->getAmount());
        } elseif ($this->currencyFormat === CurrencyFormat::SymbolBeforeWithSpace) {
            return $currencyService->getCurrencySymbolForMoney($money).' '.$this->formatNumber($money->getAmount());
        } elseif ($this->currencyFormat === CurrencyFormat::SymbolAfterWithSpace) {
            return $this->formatNumber($money->getAmount()).' '.$currencyService->getCurrencySymbolForMoney($money);
        }
    }

    public function formatTime(CarbonInterface $time): string
    {
        if ($this->timeFormat === TimeFormat::TwelveHours) {
            return $time->format('h:i a'); // Examples: "11:01 am", "1:02 am"
        } elseif ($this->timeFormat === TimeFormat::TwentyFourHours) {
            return $time->format('H:i'); // Examples: "23:01", "01:02"
        }
    }

    public function formatDate(CarbonInterface $date): string
    {
        return $date->format($this->dateFormat->toCarbonFormat());
    }

    public function setDateFormat(DateFormat $dateFormat): void
    {
        $this->dateFormat = $dateFormat;
    }

    public function setCurrencyFormat(CurrencyFormat $currencyFormat): void
    {
        $this->currencyFormat = $currencyFormat;
    }

    public function setIntervalFormat(IntervalFormat $intervalFormat): void
    {
        $this->intervalFormat = $intervalFormat;
    }

    public function setTimeFormat(TimeFormat $timeFormat): void
    {
        $this->timeFormat = $timeFormat;
    }

    public function setNumberFormat(NumberFormat $numberFormat): void
    {
        $this->numberFormat = $numberFormat;
    }
}
