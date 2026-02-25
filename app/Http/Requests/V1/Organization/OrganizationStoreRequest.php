<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Organization;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;

/**
 * @property Organization $organization Organization from model binding
 */
class OrganizationStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function getName(): string
    {
        return (string) $this->input('name');
    }
}
