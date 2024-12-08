<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Report;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\Weekday;
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
}
