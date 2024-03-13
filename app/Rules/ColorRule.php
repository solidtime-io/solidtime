<?php

declare(strict_types=1);

namespace App\Rules;

use App\Service\ColorService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ColorRule implements ValidationRule
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
        if (! app(ColorService::class)->isValid($value)) {
            $fail(__('validation.color'));

            return;
        }
    }
}
