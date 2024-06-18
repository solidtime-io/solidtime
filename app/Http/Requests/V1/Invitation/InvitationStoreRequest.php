<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invitation;

use App\Enums\Role;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class InvitationStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'role' => [
                'required',
                'string',
                Rule::enum(Role::class)
                    ->except([Role::Owner, Role::Placeholder]),
            ],
        ];
    }

    public function getRole(): Role
    {
        return Role::from($this->input('role'));
    }
}
