<?php

declare(strict_types=1);

namespace App\Service\Dto;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\TimeEntryRoundingType;
use App\Enums\Weekday;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportPropertiesDto implements Castable
{
    public TimeEntryAggregationType $group;

    public TimeEntryAggregationType $subGroup;

    public TimeEntryAggregationTypeInterval $historyGroup;

    public Weekday $weekStart;

    public string $timezone;

    public Carbon $start;

    public Carbon $end;

    public ?bool $active = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $memberIds = null;

    public ?bool $billable = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $clientIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $projectIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $tagIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $taskIds = null;

    public ?TimeEntryRoundingType $roundingType = null;

    public ?int $roundingMinutes = null;

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array<string, mixed>  $arguments
     * @return CastsAttributes<ReportPropertiesDto, ReportPropertiesDto>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            private const array REQUIRED_PROPERTIES = [
                'group',
                'subGroup',
                'historyGroup',
                'weekStart',
                'timezone',
                'start',
                'end',
                'active',
                'memberIds',
                'billable',
                'clientIds',
                'projectIds',
                'tagIds',
                'taskIds',
            ];

            public function get(Model $model, string $key, mixed $value, array $attributes): ReportPropertiesDto
            {
                if (! is_string($value)) {
                    throw new \InvalidArgumentException('The given value is not a string');
                }
                $data = json_decode($value, false);
                if ($data === null) {
                    throw new \InvalidArgumentException('The given value is not a JSON string');
                }
                foreach (self::REQUIRED_PROPERTIES as $property) {
                    if (! property_exists($data, $property)) {
                        throw new \InvalidArgumentException('The given JSON string does not contain the required property "'.$property.'"');
                    }
                }
                $dto = new ReportPropertiesDto;
                $dto->end = $data->end !== null ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $data->end) : null;
                $dto->start = $data->start !== null ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $data->start) : null;
                $dto->active = $data->active;
                $dto->memberIds = $data->memberIds !== null ? ReportPropertiesDto::idArrayToCollection($data->memberIds) : null;
                $dto->billable = $data->billable;
                $dto->clientIds = $data->clientIds !== null ? ReportPropertiesDto::idArrayToCollection($data->clientIds) : null;
                $dto->projectIds = $data->projectIds !== null ? ReportPropertiesDto::idArrayToCollection($data->projectIds) : null;
                $dto->tagIds = $data->tagIds !== null ? ReportPropertiesDto::idArrayToCollection($data->tagIds) : null;
                $dto->taskIds = $data->taskIds ? ReportPropertiesDto::idArrayToCollection($data->taskIds) : null;
                $dto->group = TimeEntryAggregationType::from($data->group);
                $dto->subGroup = TimeEntryAggregationType::from($data->subGroup);
                $dto->historyGroup = TimeEntryAggregationTypeInterval::from($data->historyGroup);
                $dto->weekStart = Weekday::from($data->weekStart);
                $dto->timezone = $data->timezone;
                // Note: roundingType was added later so it is possible that the value is missing in persisted reports in the DB
                $dto->roundingType = isset($data->roundingType) ? TimeEntryRoundingType::from($data->roundingType) : null;
                // Note: roundingMinutes was added later so it is possible that the value is missing in persisted reports in the DB
                $dto->roundingMinutes = isset($data->roundingMinutes) ? (int) $data->roundingMinutes : null;

                return $dto;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): string
            {
                if (! ($value instanceof ReportPropertiesDto)) {
                    throw new \InvalidArgumentException('The given value is not an instance of ReportPropertiesDto');
                }

                $data = (object) [
                    'end' => $value->end->toIso8601ZuluString(),
                    'start' => $value->start->toIso8601ZuluString(),
                    'active' => $value->active,
                    'memberIds' => $value->memberIds?->toArray(),
                    'billable' => $value->billable,
                    'clientIds' => $value->clientIds?->toArray(),
                    'projectIds' => $value->projectIds?->toArray(),
                    'tagIds' => $value->tagIds?->toArray(),
                    'taskIds' => $value->taskIds?->toArray(),
                    'group' => $value->group->value,
                    'subGroup' => $value->subGroup->value,
                    'historyGroup' => $value->historyGroup->value,
                    'weekStart' => $value->weekStart->value,
                    'timezone' => $value->timezone,
                    'roundingType' => $value->roundingType?->value,
                    'roundingMinutes' => $value->roundingMinutes,
                ];

                $jsonString = json_encode($data);
                if ($jsonString === false) {
                    throw new \InvalidArgumentException('Could not encode the given data to a JSON string');
                }

                return $jsonString;
            }
        };
    }

    /**
     * @param  array<mixed>  $ids
     * @return Collection<int, string>
     */
    public static function idArrayToCollection(array $ids): Collection
    {
        $collection = new Collection;
        foreach ($ids as $id) {
            if (! is_string($id)) {
                throw new \InvalidArgumentException('The given ID is not a string');
            }
            if (! Str::isUuid($id)) {
                throw new \InvalidArgumentException('The given ID is not a valid UUID');
            }
            $collection->push($id);
        }

        return $collection;
    }

    /**
     * @param  array<mixed>|null  $memberIds
     */
    public function setMemberIds(?array $memberIds): void
    {
        $this->memberIds = $memberIds !== null ? ReportPropertiesDto::idArrayToCollection($memberIds) : null;
    }

    /**
     * @param  array<mixed>|null  $clientIds
     */
    public function setClientIds(?array $clientIds): void
    {
        $this->clientIds = $clientIds !== null ? ReportPropertiesDto::idArrayToCollection($clientIds) : null;
    }

    /**
     * @param  array<mixed>|null  $projectIds
     */
    public function setProjectIds(?array $projectIds): void
    {
        $this->projectIds = $projectIds !== null ? ReportPropertiesDto::idArrayToCollection($projectIds) : null;
    }

    /**
     * @param  array<mixed>|null  $tagIds
     */
    public function setTagIds(?array $tagIds): void
    {
        $this->tagIds = $tagIds !== null ? ReportPropertiesDto::idArrayToCollection($tagIds) : null;
    }

    /**
     * @param  array<mixed>|null  $taskIds
     */
    public function setTaskIds(?array $taskIds): void
    {
        $this->taskIds = $taskIds !== null ? ReportPropertiesDto::idArrayToCollection($taskIds) : null;
    }
}
