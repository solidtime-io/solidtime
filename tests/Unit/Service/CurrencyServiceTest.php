<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\CurrencyService;
use Brick\Money\Currency;
use Brick\Money\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(CurrencyService::class)]
class CurrencyServiceTest extends TestCaseWithDatabase
{
    private CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService;
    }

    public function test_get_currency_symbol_for_currency_eur(): void
    {
        // Arrange
        $money = Money::of(1, Currency::of('EUR'));

        // Act
        $symbol = $this->currencyService->getCurrencySymbolForMoney($money);

        // Assert
        $this->assertSame('€', $symbol);
    }

    public function test_get_currency_symbol_for_currency_usd(): void
    {
        // Arrange
        $money = Money::of(1, Currency::of('USD'));

        // Act
        $symbol = $this->currencyService->getCurrencySymbolForMoney($money);

        // Assert
        $this->assertSame('$', $symbol);
    }

    public function test_get_currency_symbol_for_currency_gbp(): void
    {
        // Arrange
        $money = Money::of(1, Currency::of('GBP'));

        // Act
        $symbol = $this->currencyService->getCurrencySymbolForMoney($money);

        // Assert
        $this->assertSame('£', $symbol);
    }

    public function test_get_currency_symbol_for_currency_cad(): void
    {
        // Arrange
        $money = Money::of(1, Currency::of('CAD'));

        // Act
        $symbol = $this->currencyService->getCurrencySymbolForMoney($money);

        // Assert
        $this->assertSame('$', $symbol);
    }

    public function test_get_currency_symbol_for_currency_cop(): void
    {
        // Arrange
        $money = Money::of(1, Currency::of('COP'));

        // Act
        $symbol = $this->currencyService->getCurrencySymbolForMoney($money);

        // Assert
        $this->assertSame('$', $symbol);
    }

    public function test_get_currency_symbol_for_currency_without_known_symbol(): void
    {
        // Arrange
        $currency = 'XXX';

        // Act
        $symbol = $this->currencyService->getCurrencySymbol($currency);

        // Assert
        $this->assertSame('XXX', $symbol);
    }
}
