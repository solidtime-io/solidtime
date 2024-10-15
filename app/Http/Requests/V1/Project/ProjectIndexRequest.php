<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Project;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProjectIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'integer',
                'min:1',
                'max:2147483647',
            ],
            'archived' => [
                'string',
                'in:true,false,all',
            ],
        ];
    }

    public function getFilterArchived(): string
    {
        return $this->input('archived', 'false');
    }
}
