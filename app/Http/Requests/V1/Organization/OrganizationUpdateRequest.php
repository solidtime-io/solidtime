<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Organization;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class OrganizationUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
            ],
            'billable_rate' => array_merge(
                [
                    'nullable',
                ],
                $this->moneyRules()
            ),
            'employees_can_see_billable_rates' => [
                'boolean',
            ],
            'prevent_overlapping_time_entries' => [
                'boolean',
            ],
            'number_format' => [
                Rule::enum(NumberFormat::class),
            ],
            'currency_format' => [
                Rule::enum(CurrencyFormat::class),
            ],
            'date_format' => [
                Rule::enum(DateFormat::class),
            ],
            'interval_format' => [
                Rule::enum(IntervalFormat::class),
            ],
            'time_format' => [
                Rule::enum(TimeFormat::class),
            ],
        ];
    }

    public function getName(): ?string
    {
        return $this->has('name') ? (string) $this->input('name') : null;
    }

    public function getNumberFormat(): ?NumberFormat
    {
        return $this->has('number_format') ? NumberFormat::from($this->input('number_format')) : null;
    }

    public function getCurrencyFormat(): ?CurrencyFormat
    {
        return $this->has('currency_format') ? CurrencyFormat::from($this->input('currency_format')) : null;
    }

    public function getDateFormat(): ?DateFormat
    {
        return $this->has('date_format') ? DateFormat::from($this->input('date_format')) : null;
    }

    public function getIntervalFormat(): ?IntervalFormat
    {
        return $this->has('interval_format') ? IntervalFormat::from($this->input('interval_format')) : null;
    }

    public function getTimeFormat(): ?TimeFormat
    {
        return $this->has('time_format') ? TimeFormat::from($this->input('time_format')) : null;
    }

    public function getBillableRate(): ?int
    {
        $input = $this->input('billable_rate');

        return $input !== null && $input !== 0 ? (int) $this->input('billable_rate') : null;
    }

    public function getEmployeesCanSeeBillableRates(): ?bool
    {
        return $this->has('employees_can_see_billable_rates') ? $this->boolean('employees_can_see_billable_rates') : null;
    }

    public function getPreventOverlappingTimeEntries(): ?bool
    {
        return $this->has('prevent_overlapping_time_entries') ? $this->boolean('prevent_overlapping_time_entries') : null;
    }
}
