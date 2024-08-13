<?php

declare(strict_types=1);

namespace App\Service\Dto;

use App\Enums\TimeEntryAggregationType;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportPropertiesDto implements Castable
{
    public ?TimeEntryAggregationType $group = null;

    public ?TimeEntryAggregationType $subGroup = null;

    public ?Carbon $start = null;

    public ?Carbon $end = null;

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
                $dto->memberIds = $data->memberIds !== null ? $this->idArrayToCollection($data->memberIds) : null;
                $dto->billable = $data->billable;
                $dto->clientIds = $data->clientIds !== null ? $this->idArrayToCollection($data->clientIds) : null;
                $dto->projectIds = $data->projectIds !== null ? $this->idArrayToCollection($data->projectIds) : null;
                $dto->tagIds = $data->tagIds !== null ? $this->idArrayToCollection($data->tagIds) : null;
                $dto->taskIds = $data->taskIds ? $this->idArrayToCollection($data->taskIds) : null;
                $dto->group = $data->group !== null ? TimeEntryAggregationType::from($data->group) : null;
                $dto->subGroup = $data->subGroup !== null ? TimeEntryAggregationType::from($data->subGroup) : null;

                return $dto;
            }

            /**
             * @param  array<mixed>  $ids
             * @return Collection<int, string>
             */
            private function idArrayToCollection(array $ids): Collection
            {
                $collection = new Collection;
                foreach ($ids as $id) {
                    if (! is_string($id)) {
                        throw new \InvalidArgumentException('The given ID is not a string');
                    }
                    if (Str::isUuid($id)) {
                        throw new \InvalidArgumentException('The given ID is not a valid UUID');
                    }
                    $collection->push($id);
                }

                return $collection;
            }

            /**
             * @param  ReportPropertiesDto  $value
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): string
            {
                if (! ($value instanceof ReportPropertiesDto)) {
                    throw new \InvalidArgumentException('The given value is not an instance of ReportPropertiesDto');
                }

                $data = (object) [
                    'end' => $value->end?->toIso8601ZuluString(),
                    'start' => $value->start?->toIso8601ZuluString(),
                    'active' => $value->active,
                    'memberIds' => $value->memberIds?->toArray(),
                    'billable' => $value->billable,
                    'clientIds' => $value->clientIds?->toArray(),
                    'projectIds' => $value->projectIds?->toArray(),
                    'tagIds' => $value->tagIds?->toArray(),
                    'taskIds' => $value->taskIds?->toArray(),
                    'group' => $value->group?->value,
                    'subGroup' => $value->subGroup?->value,
                ];

                $jsonString = json_encode($data);
                if ($jsonString === false) {
                    throw new \InvalidArgumentException('Could not encode the given data to a JSON string');
                }

                return $jsonString;
            }
        };
    }
}
