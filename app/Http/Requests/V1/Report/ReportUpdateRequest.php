<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Report;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * @property Organization $organization Organization from model binding
 */
class ReportUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_public' => [
                'boolean',
            ],
            'public_until' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
                'after:now',
            ],
        ];
    }

    public function getName(): string
    {
        return (string) $this->input('name');
    }

    public function getDescription(): ?string
    {
        return $this->input('description');
    }

    public function getIsPublic(): bool
    {
        return (bool) $this->input('is_public');
    }

    public function getPublicUntil(): ?Carbon
    {
        $publicUntil = $this->input('public_until');

        return $publicUntil === null ? null : Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $publicUntil);
    }
}
