<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum CurrencyFormat: string
{
    use LaravelEnumHelper;

    case ISOCodeBeforeWithSpace = 'iso-code-before-with-space';
    case ISOCodeAfterWithSpace = 'iso-code-after-with-space';

    case SymbolBefore = 'symbol-before';

    case SymbolAfter = 'symbol-after';

    case SymbolBeforeWithSpace = 'symbol-before-with-space';

    case SymbolAfterWithSpace = 'symbol-after-with-space';

    /**
     * @return array<string, string>
     */
    public static function toSelectArray(): array
    {
        $selectArray = [];
        foreach (self::values() as $value) {
            $selectArray[(string) $value] = (string) __('enum.currency_format.'.$value);
        }

        return $selectArray;
    }
}
