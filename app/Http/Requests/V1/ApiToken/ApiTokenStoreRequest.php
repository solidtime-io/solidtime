<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ApiToken;

use App\Http\Requests\V1\BaseFormRequest;

class ApiTokenStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
        ];
    }

    public function getName(): string
    {
        return $this->input('name');
    }
}
