<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Goal;

use App\Enums\GoalType;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class GoalIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|LegacyValidationRule|\Closure>>
     */
    public function rules(): array
    {
        return [
            // Only return goals of this type (personal, organization)
            'type' => [
                'nullable',
                'string',
                Rule::enum(GoalType::class),
            ],
            // Filter by archived status, "true" only archived, "false" only not archived (default), "all" both
            'archived' => [
                'nullable',
                'string',
                'in:true,false,all',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:2147483647',
            ],
        ];
    }

    public function getType(): ?GoalType
    {
        if ($this->input('type') === null) {
            return null;
        }

        return GoalType::from($this->input('type'));
    }

    public function getArchivedFilter(): string
    {
        return (string) $this->input('archived', 'false');
    }
}
