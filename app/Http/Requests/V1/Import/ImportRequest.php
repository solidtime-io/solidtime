<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Import;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ImportRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
            ],
            'data' => [
                'required',
                'string',
            ],
        ];
    }
}
