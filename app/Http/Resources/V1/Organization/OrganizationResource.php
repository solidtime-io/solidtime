<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Organization;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Http\Resources\V1\BaseResource;
use App\Models\Organization;
use App\Service\CurrencyService;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class OrganizationResource extends BaseResource
{
    private bool $showBillableRate;

    /**
     * Create a new resource instance.
     *
     * @return void
     */
    public function __construct(Organization $resource, bool $showBillableRate)
    {
        parent::__construct($resource);

        $this->showBillableRate = $showBillableRate;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        $currencyService = app(CurrencyService::class);

        return [
            /** ID */
            'id' => $this->resource->id,
            /** Name */
            'name' => $this->resource->name,
            /** Personal organizations automatically created after registration */
            'is_personal' => $this->resource->personal_team,
            /** Billable rate in cents per hour */
            'billable_rate' => $this->showBillableRate ? $this->resource->billable_rate : null,
            /** Can members of the organization with role "employee" see the billable rates */
            'employees_can_see_billable_rates' => $this->resource->employees_can_see_billable_rates,
            /** Can members of the organization with role "employee" manage tasks in public projects and projects they are assigned to */
            'employees_can_manage_tasks' => $this->resource->employees_can_manage_tasks,
            /** Prevent creating overlapping time entries (only new entries) */
            'prevent_overlapping_time_entries' => $this->resource->prevent_overlapping_time_entries,
            /** Whether members of the organization can track breaks */
            'breaks_enabled' => $this->resource->breaks_enabled,
            /** Currency code (ISO 4217) */
            'currency' => $this->resource->currency,
            /** @var string $currency_symbol Currency symbol */
            'currency_symbol' => $currencyService->getCurrencySymbol($this->resource->currency),
            /** @var NumberFormat $number_format Number format */
            'number_format' => $this->resource->number_format->value,
            /** @var CurrencyFormat $currency_format Currency format */
            'currency_format' => $this->resource->currency_format->value,
            /** @var DateFormat $date_format Date format */
            'date_format' => $this->resource->date_format->value,
            /** @var IntervalFormat $interval_format Interval format */
            'interval_format' => $this->resource->interval_format->value,
            /** @var TimeFormat $time_format Time format */
            'time_format' => $this->resource->time_format->value,
        ];
    }
}
