<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\VacationRequest;

use App\Enums\VacationRequestStatus;
use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class VacationRequestUpdateRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(VacationRequestStatus::class)],
        ];
    }
}
