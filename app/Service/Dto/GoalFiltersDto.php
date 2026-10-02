<?php

declare(strict_types=1);

namespace App\Service\Dto;

use App\Enums\TagMatchType;
use App\Enums\TimeEntryType;
use App\Service\TimeEntryFilter;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Persisted filter set of a goal. Defines which time entries count towards the goal.
 * A null value means "no restriction" for that dimension. The ID setters store an empty array as null,
 * so "no restriction" has a single representation.
 */
class GoalFiltersDto implements Castable
{
    /**
     * Members whose time entries count. Only used by goals that count every member, null means every member.
     *
     * @var Collection<int, string>|null
     */
    public ?Collection $memberIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $projectIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $taskIds = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $tagIds = null;

    public ?TagMatchType $tagMatchType = null;

    /**
     * @var Collection<int, string>|null
     */
    public ?Collection $clientIds = null;

    public ?bool $billable = null;

    public ?TimeEntryType $timeEntryType = null;

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array<string, mixed>  $arguments
     * @return CastsAttributes<GoalFiltersDto, GoalFiltersDto>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            private const array REQUIRED_PROPERTIES = [
                'memberIds',
                'projectIds',
                'taskIds',
                'tagIds',
                'tagMatchType',
                'clientIds',
                'billable',
                'timeEntryType',
            ];

            public function get(Model $model, string $key, mixed $value, array $attributes): GoalFiltersDto
            {
                if (! is_string($value)) {
                    throw new \InvalidArgumentException('The given value is not a string');
                }
                $data = json_decode($value, false);
                if (! is_object($data)) {
                    throw new \InvalidArgumentException('The given value is not a JSON object string');
                }
                foreach (self::REQUIRED_PROPERTIES as $property) {
                    if (! property_exists($data, $property)) {
                        throw new \InvalidArgumentException('The given JSON string does not contain the required property "'.$property.'"');
                    }
                }
                $dto = new GoalFiltersDto;
                $dto->setMemberIds(isset($data->memberIds) ? (array) $data->memberIds : null);
                $dto->setProjectIds(isset($data->projectIds) ? (array) $data->projectIds : null);
                $dto->setTaskIds(isset($data->taskIds) ? (array) $data->taskIds : null);
                $dto->setTagIds(isset($data->tagIds) ? (array) $data->tagIds : null);
                $dto->tagMatchType = isset($data->tagMatchType) ? TagMatchType::from($data->tagMatchType) : null;
                $dto->setClientIds(isset($data->clientIds) ? (array) $data->clientIds : null);
                $dto->billable = isset($data->billable) ? (bool) $data->billable : null;
                $dto->timeEntryType = isset($data->timeEntryType) ? TimeEntryType::from($data->timeEntryType) : null;

                return $dto;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): string
            {
                if (! ($value instanceof GoalFiltersDto)) {
                    throw new \InvalidArgumentException('The given value is not an instance of GoalFiltersDto');
                }

                $data = (object) [
                    'memberIds' => $value->memberIds?->toArray(),
                    'projectIds' => $value->projectIds?->toArray(),
                    'taskIds' => $value->taskIds?->toArray(),
                    'tagIds' => $value->tagIds?->toArray(),
                    'tagMatchType' => $value->tagMatchType?->value,
                    'clientIds' => $value->clientIds?->toArray(),
                    'billable' => $value->billable,
                    'timeEntryType' => $value->timeEntryType?->value,
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
     * @param  array<mixed>|null  $memberIds
     */
    public function setMemberIds(?array $memberIds): void
    {
        $this->memberIds = $memberIds !== null && count($memberIds) > 0 ? TimeEntryFilter::idArrayToCollection($memberIds) : null;
    }

    /**
     * @param  array<mixed>|null  $projectIds
     */
    public function setProjectIds(?array $projectIds): void
    {
        $this->projectIds = $projectIds !== null && count($projectIds) > 0 ? TimeEntryFilter::idArrayToCollection($projectIds) : null;
    }

    /**
     * @param  array<mixed>|null  $taskIds
     */
    public function setTaskIds(?array $taskIds): void
    {
        $this->taskIds = $taskIds !== null && count($taskIds) > 0 ? TimeEntryFilter::idArrayToCollection($taskIds) : null;
    }

    /**
     * @param  array<mixed>|null  $tagIds
     */
    public function setTagIds(?array $tagIds): void
    {
        $this->tagIds = $tagIds !== null && count($tagIds) > 0 ? TimeEntryFilter::idArrayToCollection($tagIds) : null;
    }

    /**
     * @param  array<mixed>|null  $clientIds
     */
    public function setClientIds(?array $clientIds): void
    {
        $this->clientIds = $clientIds !== null && count($clientIds) > 0 ? TimeEntryFilter::idArrayToCollection($clientIds) : null;
    }
}
