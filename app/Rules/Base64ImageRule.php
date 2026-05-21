<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Base64File;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Base64ImageRule implements ValidationRule
{
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
    ];

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

        $file = Base64File::decode($value);
        if ($file === null || ! in_array($file['mime_type'], self::ALLOWED_MIME_TYPES, true)) {
            $fail(__('validation.mimes', ['values' => 'jpg, png']));
        }
    }
}
