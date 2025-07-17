<?php

declare(strict_types=1);

namespace App\Service;

use Brick\Money\ISOCurrencyProvider;
use Brick\Money\Money;

class CurrencyService
{
    /**
     * @source https://gist.github.com/stephenfrank/a8245c2486f3e546107c5363706ac93e
     *
     * @const array<string, array<{ symbol: string }>>
     */
    private const array CURRENCIES = [
        'ALL' => [
            'symbol' => 'L',
        ],
        'AFN' => [
            'symbol' => '؋',
        ],
        'ARS' => [
            'symbol' => '$',
        ],
        'AWG' => [
            'symbol' => 'ƒ',
        ],
        'AUD' => [
            'symbol' => '$',
        ],
        'AZN' => [
            'symbol' => '₼',
        ],
        'BSD' => [
            'symbol' => '$',
        ],
        'BBD' => [
            'symbol' => '$',
        ],
        'BDT' => [
            'symbol' => '৳',
        ],
        'BYR' => [
            'symbol' => 'Br',
        ],
        'BZD' => [
            'symbol' => 'BZ$',
        ],
        'BMD' => [
            'symbol' => '$',
        ],
        'BOB' => [
            'symbol' => '$b',
        ],
        'BAM' => [
            'symbol' => 'KM',
        ],
        'BWP' => [
            'symbol' => 'P',
        ],
        'BGN' => [
            'symbol' => 'лв',
        ],
        'BRL' => [
            'symbol' => 'R$',
        ],
        'BND' => [
            'symbol' => '$',
        ],
        'KHR' => [
            'symbol' => '៛',
        ],
        'CAD' => [
            'symbol' => '$',
        ],
        'KYD' => [
            'symbol' => '$',
        ],
        'CLP' => [
            'symbol' => '$',
        ],
        'CNY' => [
            'symbol' => '¥',
        ],
        'COP' => [
            'symbol' => '$',
        ],
        'CRC' => [
            'symbol' => '₡',
        ],
        'HRK' => [
            'symbol' => 'kn',
        ],
        'CUP' => [
            'symbol' => '₱',
        ],
        'CZK' => [
            'symbol' => 'Kč',
        ],
        'DKK' => [
            'symbol' => 'kr',
        ],
        'DOP' => [
            'symbol' => 'RD$',
        ],
        'XCD' => [
            'symbol' => '$',
        ],
        'EGP' => [
            'symbol' => '£',
        ],
        'SVC' => [
            'symbol' => '$',
        ],
        'EEK' => [
            'symbol' => 'kr',
        ],
        'EUR' => [
            'symbol' => '€',
        ],
        'FKP' => [
            'symbol' => '£',
        ],
        'FJD' => [
            'symbol' => '$',
        ],
        'GHC' => [
            'symbol' => '₵',
        ],
        'GIP' => [
            'symbol' => '£',
        ],
        'GTQ' => [
            'symbol' => 'Q',
        ],
        'GGP' => [
            'symbol' => '£',
        ],
        'GYD' => [
            'symbol' => '$',
        ],
        'HNL' => [
            'symbol' => 'L',
        ],
        'HKD' => [
            'symbol' => '$',
        ],
        'HUF' => [
            'symbol' => 'Ft',
        ],
        'ISK' => [
            'symbol' => 'kr',
        ],
        'INR' => [
            'symbol' => '₹',
        ],
        'IDR' => [
            'symbol' => 'Rp',
        ],
        'IRR' => [
            'symbol' => '﷼',
        ],
        'IMP' => [
            'symbol' => '£',
        ],
        'ILS' => [
            'symbol' => '₪',
        ],
        'JMD' => [
            'symbol' => 'J$',
        ],
        'JPY' => [
            'symbol' => '¥',
        ],
        'JEP' => [
            'symbol' => '£',
        ],
        'KZT' => [
            'symbol' => 'лв',
        ],
        'KPW' => [
            'symbol' => '₩',
        ],
        'KRW' => [
            'symbol' => '₩',
        ],
        'KGS' => [
            'symbol' => 'лв',
        ],
        'LAK' => [
            'symbol' => '₭',
        ],
        'LVL' => [
            'symbol' => 'Ls',
        ],
        'LBP' => [
            'symbol' => '£',
        ],
        'LRD' => [
            'symbol' => '$',
        ],
        'LTL' => [
            'symbol' => 'Lt',
        ],
        'MKD' => [
            'symbol' => 'ден',
        ],
        'MYR' => [
            'symbol' => 'RM',
        ],
        'MUR' => [
            'symbol' => '₨',
        ],
        'MXN' => [
            'symbol' => '$',
        ],
        'MNT' => [
            'symbol' => '₮',
        ],
        'MZN' => [
            'symbol' => 'MT',
        ],
        'NAD' => [
            'symbol' => '$',
        ],
        'NPR' => [
            'symbol' => '₨',
        ],
        'ANG' => [
            'symbol' => 'ƒ',
        ],
        'NZD' => [
            'symbol' => '$',
        ],
        'NIO' => [
            'symbol' => 'C$',
        ],
        'NGN' => [
            'symbol' => '₦',
        ],
        'NOK' => [
            'symbol' => 'kr',
        ],
        'OMR' => [
            'symbol' => '﷼',
        ],
        'PKR' => [
            'symbol' => '₨',
        ],
        'PAB' => [
            'symbol' => 'B/.',
        ],
        'PYG' => [
            'symbol' => 'Gs',
        ],
        'PEN' => [
            'symbol' => 'S/.',
        ],
        'PHP' => [
            'symbol' => '₱',
        ],
        'PLN' => [
            'symbol' => 'zł',
        ],
        'QAR' => [
            'symbol' => '﷼',
        ],
        'RON' => [
            'symbol' => 'lei',
        ],
        'RUB' => [
            'symbol' => '₽',
        ],
        'SHP' => [
            'symbol' => '£',
        ],
        'SAR' => [
            'symbol' => '﷼',
        ],
        'RSD' => [
            'symbol' => 'Дин.',
        ],
        'SCR' => [
            'symbol' => '₨',
        ],
        'SGD' => [
            'symbol' => '$',
        ],
        'SBD' => [
            'symbol' => '$',
        ],
        'SOS' => [
            'symbol' => 'S',
        ],
        'ZAR' => [
            'symbol' => 'R',
        ],
        'LKR' => [
            'symbol' => '₨',
        ],
        'SEK' => [
            'symbol' => 'kr',
        ],
        'CHF' => [
            'symbol' => 'CHF',
        ],
        'SRD' => [
            'symbol' => '$',
        ],
        'SYP' => [
            'symbol' => '£',
        ],
        'TWD' => [
            'symbol' => 'NT$',
        ],
        'THB' => [
            'symbol' => '฿',
        ],
        'TTD' => [
            'symbol' => 'TT$',
        ],
        'TRY' => [
            'symbol' => '₺',
        ],
        'TRL' => [
            'symbol' => '₤',
        ],
        'TVD' => [
            'symbol' => '$',
        ],
        'UAH' => [
            'symbol' => '₴',
        ],
        'GBP' => [
            'symbol' => '£',
        ],
        'UGX' => [
            'symbol' => 'USh',
        ],
        'USD' => [
            'symbol' => '$',
        ],
        'UYU' => [
            'symbol' => '$U',
        ],
        'UZS' => [
            'symbol' => 'лв',
        ],
        'VEF' => [
            'symbol' => 'Bs',
        ],
        'VND' => [
            'symbol' => '₫',
        ],
        'YER' => [
            'symbol' => '﷼',
        ],
        'ZWD' => [
            'symbol' => 'Z$',
        ],
    ];

    public function getCurrencySymbolForMoney(Money $money): string
    {
        return $this->getCurrencySymbol($money->getCurrency()->getCurrencyCode());
    }

    public function getCurrencySymbol(string $currencyCode): string
    {
        if (isset(self::CURRENCIES[$currencyCode]['symbol'])) {
            return self::CURRENCIES[$currencyCode]['symbol'];
        }

        return $currencyCode;
    }

    public function getRandomCurrencyCode(): string
    {
        $currencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $currencyCodes = array_keys($currencies);

        return $currencyCodes[array_rand($currencyCodes)];
    }
}
