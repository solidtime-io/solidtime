<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ProjectMember;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property Organization $organization Organization from model binding
 */
class ProjectMemberUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'billable_rate' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
