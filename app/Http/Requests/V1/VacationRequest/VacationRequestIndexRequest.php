<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\VacationRequest;

use App\Http\Requests\V1\BaseFormRequest;

class VacationRequestIndexRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'string', 'uuid'],
            'status' => ['nullable', 'string'],
        ];
    }
}
