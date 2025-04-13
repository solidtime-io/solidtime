<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Report;

use App\Http\Resources\V1\BaseResource;
use App\Models\Report;
use Illuminate\Http\Request;

/**
 * @property Report $resource
 *
 * @phpstan-type Data array{
 *          grouped_type: string|null,
 *          grouped_data: null|array<array{
 *              key: string|null,
 *              description: string|null,
 *              color: string|null,
 *              seconds: int,
 *              cost: int|null,
 *              grouped_type: string|null,
 *              grouped_data: null|array<array{
 *                  key: string|null,
 *                  description: string|null,
 *                  color: string|null,
 *                  seconds: int,
 *                  cost: int|null,
 *                  grouped_type: null,
 *                  grouped_data: null
 *              }>
 *          }>,
 *          seconds: int,
 *          cost: int|null
 *    }
 */
class DetailedWithDataReportResource extends BaseResource
{
    /**
     * @var Data
     */
    private array $data;

    /**
     * @var Data
     */
    private array $historyData;

    /**
     * @param  Data  $data
     * @param  Data  $historyData
     */
    public function __construct(Report $resource, array $data, array $historyData)
    {
        parent::__construct($resource);
        $this->data = $data;
        $this->historyData = $historyData;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|Data|array<string, string|bool|int|null|array<int, string>>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $name Name */
            'name' => $this->resource->name,
            /** @var string|null $email Description */
            'description' => $this->resource->description,
            /** @var string|null $public_until Date until the report is public */
            'public_until' => $this->formatDateTime($this->resource->public_until),
            /** @var string $currency Currency code (ISO 4217) */
            'currency' => $this->resource->organization->currency,
            'properties' => [
                /** @var string $group Type of first grouping */
                'group' => $this->resource->properties->group->value,
                /** @var string $sub_group Type of second grouping */
                'sub_group' => $this->resource->properties->subGroup->value,
                /** @var string $history_group Type of grouping of the historic aggregation (time chart) */
                'history_group' => $this->resource->properties->historyGroup->value,
                /** @var string $start Start date of the report */
                'start' => $this->formatDateTime($this->resource->properties->start),
                /** @var string $end End date of the report */
                'end' => $this->formatDateTime($this->resource->properties->end),
            ],
            /** @var array{
             *        grouped_type: string|null,
             *        grouped_data: null|array<array{
             *            key: string|null,
             *            description: string|null,
             *            color: string|null,
             *            seconds: int,
             *            cost: int,
             *            grouped_type: string|null,
             *            grouped_data: null|array<array{
             *                key: string|null,
             *                description: string|null,
             *                color: string|null,
             *                seconds: int,
             *                cost: int,
             *                grouped_type: null,
             *                grouped_data: null
             *            }>
             *        }>,
             *        seconds: int,
             *        cost: int
             *  } $data Aggregated data
             */
            'data' => $this->data,
            /** @var array{
             *        grouped_type: string|null,
             *        grouped_data: null|array<array{
             *            key: string|null,
             *            description: string|null,
             *            seconds: int,
             *            cost: int,
             *            grouped_type: string|null,
             *            grouped_data: null|array<array{
             *                key: string|null,
             *                description: string|null,
             *                seconds: int,
             *                cost: int,
             *                grouped_type: null,
             *                grouped_data: null
             *            }>
             *        }>,
             *        seconds: int,
             *        cost: int
             *  } $history_data Historic aggregated data
             */
            'history_data' => $this->historyData,
        ];
    }
}
