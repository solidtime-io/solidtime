<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Member;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @property Organization $organization
 */
class MemberDestroyRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'delete_related' => [
                'string',
                'in:true,false',
            ],
        ];
    }

    public function getDeleteRelated(): bool
    {
        return $this->input('delete_related', 'false') === 'true';
    }
}
