<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ProjectMember;

use App\Enums\ProjectMemberRole;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class ProjectMemberUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'billable_rate' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'role' => [
                'string',
                Rule::enum(ProjectMemberRole::class),
            ],
        ];
    }

    public function getBillableRate(): ?int
    {
        $input = $this->input('billable_rate');

        return $input !== null && ((int) $input) !== 0 ? (int) $this->validated('billable_rate') : null;
    }

    public function getRole(): ?ProjectMemberRole
    {
        return $this->has('role') ? ProjectMemberRole::from($this->validated('role')) : null;
    }
}
