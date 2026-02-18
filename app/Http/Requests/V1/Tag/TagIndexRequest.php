<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Tag;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TagIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'integer',
                'min:1',
                'max:2147483647',
            ],
        ];
    }
}
