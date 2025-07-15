<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Service\LocalizationService;
use Brick\Money\Currency;
use Brick\Money\Money;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(LocalizationService::class)]
class LocalizationServiceTest extends TestCaseWithDatabase
{
    private LocalizationService $localizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->localizationService = new LocalizationService(
            CurrencyFormat::SymbolAfterWithSpace,
            DateFormat::PointSeparatedDMYYYY,
            TimeFormat::TwelveHours,
            NumberFormat::ThousandsPointDecimalComma,
            IntervalFormat::Decimal,
        );
    }

    public function test_format_interval_with_type_decimal_and_number_format_thousands_comma_decimal_point(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::Decimal);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsCommaDecimalPoint);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30,001.05 h', $formatted);
    }

    public function test_format_interval_with_type_decimal_and_number_format_thousands_space_decimal_point(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::Decimal);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalPoint);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30 001.05 h', $formatted);
    }

    public function test_format_interval_with_type_decimal_and_number_format_thousands_point_decimal_comma(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::Decimal);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsPointDecimalComma);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30.001,05 h', $formatted);
    }

    public function test_format_interval_with_type_decimal_and_number_format_thousands_apostrophe_decimal_point(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::Decimal);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsApostropheDecimalPoint);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30\'001.05 h', $formatted);
    }

    public function test_format_interval_with_type_hours_minutes(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::HoursMinutes);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30001h 03m', $formatted);
    }

    public function test_format_interval_with_type_hours_minutes_colon_separated(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::HoursMinutesColonSeparated);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30001:03', $formatted);
    }

    public function test_format_interval_with_type_hours_minutes_seconds_colon_separated(): void
    {
        // Arrange
        $interval = CarbonInterval::seconds(4 + (60 * 3) + (60 * 60 * 30001));
        $this->localizationService->setIntervalFormat(IntervalFormat::HoursMinutesSecondsColonSeparated);

        // Act
        $formatted = $this->localizationService->formatInterval($interval);

        // Assert
        $this->assertSame('30001:03:04', $formatted);
    }

    public function test_format_currency_with_type_symbol_after_with_space_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::SymbolAfterWithSpace);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('1 234 567,89 €', $formatted);
    }

    public function test_format_currency_with_type_symbol_before_with_space_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::SymbolBeforeWithSpace);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('€ 1 234 567,89', $formatted);
    }

    public function test_format_currency_with_type_symbol_before_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::SymbolBefore);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('€1 234 567,89', $formatted);
    }

    public function test_format_currency_with_type_symbol_after_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::SymbolAfter);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('1 234 567,89€', $formatted);
    }

    public function test_format_currency_with_type_iso_code_after_with_space_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::ISOCodeAfterWithSpace);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('1 234 567,89 EUR', $formatted);
    }

    public function test_format_currency_with_type_iso_code_before_with_space_and_number_format_thousands_space_decimal_comma(): void
    {
        // Arrange
        $this->localizationService->setCurrencyFormat(CurrencyFormat::ISOCodeBeforeWithSpace);
        $this->localizationService->setNumberFormat(NumberFormat::ThousandsSpaceDecimalComma);
        $money = Money::of(1234567.89, Currency::of('EUR'));

        // Act
        $formatted = $this->localizationService->formatCurrency($money);

        // Assert
        $this->assertSame('EUR 1 234 567,89', $formatted);
    }

    public function test_format_date_with_type_slash_separated_ddmmy(): void
    {
        // Arrange
        $this->localizationService->setDateFormat(DateFormat::SlashSeparatedDDMMYYYY);
        $date = Carbon::createFromDate(2001, 2, 3);

        // Act
        $formatted = $this->localizationService->formatDate($date);

        // Assert
        $this->assertSame('03/02/2001', $formatted);
    }

    public function test_format_time_with_type_twelve_hours(): void
    {
        // Arrange
        $this->localizationService->setTimeFormat(TimeFormat::TwelveHours);
        $time = Carbon::createFromTime(19, 9, 8);

        // Act
        $formatted = $this->localizationService->formatTime($time);

        // Assert
        $this->assertSame('07:09 pm', $formatted);
    }

    public function test_format_time_with_type_twenty_four_hours(): void
    {
        // Arrange
        $this->localizationService->setTimeFormat(TimeFormat::TwentyFourHours);
        $time = Carbon::createFromTime(14, 9, 8);

        // Act
        $formatted = $this->localizationService->formatTime($time);

        // Assert
        $this->assertSame('14:09', $formatted);
    }
}
