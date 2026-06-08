<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UserUpdateCurrentOrganizationRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'organization_id' => [
                'required',
                'string',
                'uuid',
            ],
        ];
    }

    public function getOrganizationId(): string
    {
        return (string) $this->input('organization_id');
    }
}
