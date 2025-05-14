<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BaseFormRequest extends FormRequest
{

    /**
     * @param bool $bigInt
     * @return list<string>
     */
    protected function moneyRules(bool $bigInt = false): array
    {
        $rules = [
            'integer',
            'min:0',
        ];
        if ($bigInt) {
            $rules[] = 'max:9223372036854775807';
        } else {
            $rules[] = 'max:2147483647';
        }

        return $rules;
    }
}
