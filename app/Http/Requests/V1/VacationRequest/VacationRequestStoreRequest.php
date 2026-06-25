<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\VacationRequest;

use App\Enums\VacationRequestType;
use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class VacationRequestStoreRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(VacationRequestType::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'gte:start_date'],
            'half_day' => ['boolean'],
            'private_note' => ['nullable', 'string', 'max:512'],
            'public_note' => ['nullable', 'string', 'max:512'],
            'member_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
