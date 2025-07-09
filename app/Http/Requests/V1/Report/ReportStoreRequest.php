<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Report;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\TimeEntryRoundingType;
use App\Enums\Weekday;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class ReportStoreRequest extends BaseFormRequest
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
            // After this date the report will be automatically set to private (is_public=false) (Format: "Y-m-d\TH:i:s\Z", UTC timezone, Example: "2000-02-22T14:58:59Z")
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
                'required',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'properties.end' => [
                'required',
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
            // Filter by project IDs, project IDs are OR combined
            'properties.project_ids' => [
                'nullable',
                'array',
            ],
            'properties.project_ids.*' => [
                'string',
                'uuid',
            ],
            // Filter by tag IDs, tag IDs are OR combined
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
            'properties.history_group' => [
                'required',
                Rule::enum(TimeEntryAggregationTypeInterval::class),
            ],
            'properties.week_start' => [
                'nullable',
                Rule::enum(Weekday::class),
            ],
            'properties.timezone' => [
                'nullable',
                'timezone:all',
            ],
            // Rounding type defined where the end of each time entry should be rounded to. For example: nearest rounds the end to the nearest x minutes group. Rounding per time entry is activated if `rounding_type` and `rounding_minutes` is not null.
            'properties.rounding_type' => [
                'nullable',
                'string',
                Rule::enum(TimeEntryRoundingType::class),
            ],
            // Defines the length of the interval that the time entry rounding rounds to.
            'properties.rounding_minutes' => [
                'nullable',
                'numeric',
                'integer',
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

    public function getPropertyStart(): Carbon
    {
        $start = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('properties.start'));
        if ($start === null) {
            throw new \LogicException('Start date validation is not working');
        }

        return $start;
    }

    public function getPropertyEnd(): Carbon
    {
        $end = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('properties.end'));
        if ($end === null) {
            throw new \LogicException('End date validation is not working');
        }

        return $end;
    }

    public function getPropertyActive(): ?bool
    {
        if ($this->has('properties.active') && $this->input('properties.active') !== null) {
            return (bool) $this->input('properties.active');
        }

        return null;
    }

    public function getPropertyBillable(): ?bool
    {
        if ($this->has('properties.billable') && $this->input('properties.billable') !== null) {
            return (bool) $this->input('properties.billable');
        }

        return null;
    }

    public function getPropertyGroup(): TimeEntryAggregationType
    {
        return TimeEntryAggregationType::from($this->input('properties.group'));
    }

    public function getPropertySubGroup(): TimeEntryAggregationType
    {
        return TimeEntryAggregationType::from($this->input('properties.sub_group'));
    }

    public function getPropertyHistoryGroup(): TimeEntryAggregationTypeInterval
    {
        return TimeEntryAggregationTypeInterval::from($this->input('properties.history_group'));
    }

    public function getPropertyRoundingType(): ?TimeEntryRoundingType
    {
        if (! $this->has('properties.rounding_type') || $this->input('properties.rounding_type') === null) {
            return null;
        }

        return TimeEntryRoundingType::from($this->input('properties.rounding_type'));
    }

    public function getPropertyRoundingMinutes(): ?int
    {
        if (! $this->has('properties.rounding_minutes') || $this->input('properties.rounding_minutes') === null) {
            return null;
        }

        return (int) $this->input('properties.rounding_minutes');
    }
}
