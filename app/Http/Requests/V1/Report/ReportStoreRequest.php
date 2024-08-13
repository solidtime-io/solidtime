<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Report;

use App\Enums\TimeEntryAggregationType;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class ReportStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|LegacyValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_public' => [
                'required',
                'boolean',
            ],
            // After this date the report will be automatically set to private (is_public=false) (ISO 8601 format, UTC timezone)
            'public_until' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
                'after:now',
            ],
            'properties' => [
                'required',
                'array',
            ],
            'properties.start' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'properties.end' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'properties.active' => [
                'nullable',
                'boolean',
            ],
            'properties.member_ids' => [
                'nullable',
                'array',
            ],
            'properties.member_ids.*' => [
                'string',
                'uuid',
            ],
            'properties.billable' => [
                'nullable',
                'boolean',
            ],
            'properties.client_ids' => [
                'nullable',
                'array',
            ],
            'properties.client_ids.*' => [
                'string',
                'uuid',
            ],
            'properties.project_ids' => [
                'nullable',
                'array',
            ],
            'properties.project_ids.*' => [
                'string',
                'uuid',
            ],
            'properties.tag_ids' => [
                'nullable',
                'array',
            ],
            'properties.tag_ids.*' => [
                'string',
                'uuid',
            ],
            'properties.task_ids' => [
                'nullable',
                'array',
            ],
            'properties.task_ids.*' => [
                'string',
                'uuid',
            ],
            'properties.group' => [
                'required',
                Rule::enum(TimeEntryAggregationType::class),
            ],

            'properties.sub_group' => [
                'required',
                Rule::enum(TimeEntryAggregationType::class),
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
