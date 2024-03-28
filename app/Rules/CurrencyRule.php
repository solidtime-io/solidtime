<?php

declare(strict_types=1);

namespace App\Rules;

use Brick\Money\ISOCurrencyProvider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CurrencyRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.string'));

            return;
        }

        $currencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        if (array_key_exists($value, $currencies)) {
            return;
        }

        $fail(__('validation.currency'));
    }
}
