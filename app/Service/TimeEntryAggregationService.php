<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\Weekday;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TimeEntryAggregationService
{
    /**
     * @param  Builder<TimeEntry>  $timeEntriesQuery
     * @return array{
     *       grouped_type: string|null,
     *       grouped_data: null|array<array{
     *           key: string|null,
     *           seconds: int,
     *           cost: int|null,
     *           grouped_type: string|null,
     *           grouped_data: null|array<array{
     *               key: string|null,
     *               seconds: int,
     *               cost: int|null,
     *               grouped_type: null,
     *               grouped_data: null
     *           }>
     *       }>,
     *       seconds: int,
     *       cost: int|null
     * }
     */
    public function getAggregatedTimeEntries(Builder $timeEntriesQuery, ?TimeEntryAggregationType $group1Type, ?TimeEntryAggregationType $group2Type, string $timezone, Weekday $startOfWeek, bool $fillGapsInTimeGroups, ?Carbon $start, ?Carbon $end, bool $showBillableRate): array
    {
        $fillGapsInTimeGroupsIsPossible = $fillGapsInTimeGroups && $start !== null && $end !== null;
        $group1Select = null;
        $group2Select = null;
        $groupBy = null;
        if ($group1Type !== null) {
            $group1Select = $this->getGroupByQuery($group1Type, $timezone, $startOfWeek);
            $groupBy = ['group_1'];
            if ($group2Type !== null) {
                $group2Select = $this->getGroupByQuery($group2Type, $timezone, $startOfWeek);
                $groupBy = ['group_1', 'group_2'];
            }
        }

        $timeEntriesQuery->selectRaw(
            ($group1Select !== null ? $group1Select.' as group_1,' : '').
            ($group2Select !== null ? $group2Select.' as group_2,' : '').
            ' round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate,'.
            ' round(
                  sum(
                      extract(epoch from (coalesce("end", now()) - start)) * (coalesce(billable_rate, 0)::float/60/60)
                  )
              ) as cost'
        );
        if ($groupBy !== null) {
            $timeEntriesQuery->groupBy($groupBy);
        }
        if ($group1Select !== null) {
            $timeEntriesQuery->orderBy('group_1');
            if ($group2Select !== null) {
                $timeEntriesQuery->orderBy('group_2');
            }
        }

        $timeEntriesAggregates = $timeEntriesQuery->get();

        if ($group1Select !== null) {
            $groupedAggregates = $timeEntriesAggregates->groupBy($group2Select !== null ? ['group_1', 'group_2'] : ['group_1']);

            $group1Response = [];
            $group1ResponseSum = 0;
            $group1ResponseCost = 0;
            foreach ($groupedAggregates as $group1 => $group1Aggregates) {
                /** @var string|int $group1 */
                $group2Response = [];
                if ($group2Select !== null) {
                    $group2ResponseSum = 0;
                    $group2ResponseCost = 0;
                    foreach ($group1Aggregates as $group2 => $aggregate) {
                        /** @var string|int $group2 */
                        /** @var Collection<int, object{aggregate: int, cost: int}> $aggregate */
                        $group2Response[] = [
                            'key' => $group2 === '' ? null : (string) $group2,
                            'seconds' => (int) $aggregate->get(0)->aggregate,
                            'cost' => $showBillableRate ? (int) $aggregate->get(0)->cost : null,
                            'grouped_type' => null,
                            'grouped_data' => null,
                        ];
                        $group2ResponseSum += (int) $aggregate->get(0)->aggregate;
                        $group2ResponseCost += (int) $aggregate->get(0)->cost;
                    }
                } else {
                    /** @var Collection<int, object{aggregate: int, cost: int}> $group1Aggregates */
                    $group2ResponseSum = (int) $group1Aggregates->get(0)->aggregate;
                    $group2ResponseCost = (int) $group1Aggregates->get(0)->cost;
                    $group2Response = null;
                }

                $group1Response[] = [
                    'key' => $group1 === '' ? null : (string) $group1,
                    'seconds' => $group2ResponseSum,
                    'cost' => $showBillableRate ? $group2ResponseCost : null,
                    'grouped_type' => $group2Type?->value,
                    'grouped_data' => $group2Response,
                ];
                $group1ResponseSum += $group2ResponseSum;
                $group1ResponseCost += $group2ResponseCost;
            }

            if ($fillGapsInTimeGroupsIsPossible) {
                $group1Response = $this->fillGapsInTimeGroups($group1Response, $group1Type, $group2Type, $timezone, $startOfWeek, $start, $end);
            }
        } else {
            $group1Response = null;
            /** @var Collection<int, object{aggregate: int, cost: int}> $timeEntriesAggregates */
            $group1ResponseSum = (int) $timeEntriesAggregates->get(0)->aggregate;
            $group1ResponseCost = (int) $timeEntriesAggregates->get(0)->cost;
        }

        return [
            'seconds' => $group1ResponseSum,
            'cost' => $showBillableRate ? $group1ResponseCost : null,
            'grouped_type' => $group1Type?->value,
            'grouped_data' => $group1Response,
        ];
    }

    /**
     * @param  Builder<TimeEntry>  $timeEntriesQuery
     * @return array{
     *       grouped_type: string|null,
     *       grouped_data: null|array<array{
     *           key: string|null,
     *           description: string|null,
     *           color: string|null,
     *           seconds: int,
     *           cost: int|null,
     *           grouped_type: string|null,
     *           grouped_data: null|array<array{
     *               key: string|null,
     *               description: string|null,
     *               color: string|null,
     *               seconds: int,
     *               cost: int|null,
     *               grouped_type: null,
     *               grouped_data: null
     *           }>
     *       }>,
     *       seconds: int,
     *       cost: int|null
     * }
     */
    public function getAggregatedTimeEntriesWithDescriptions(Builder $timeEntriesQuery, ?TimeEntryAggregationType $group1Type, ?TimeEntryAggregationType $group2Type, string $timezone, Weekday $startOfWeek, bool $fillGapsInTimeGroups, ?Carbon $start, ?Carbon $end, bool $showBillableRate): array
    {
        $aggregatedTimeEntries = $this->getAggregatedTimeEntries($timeEntriesQuery, $group1Type, $group2Type, $timezone, $startOfWeek, $fillGapsInTimeGroups, $start, $end, $showBillableRate);

        $keysGroup1 = [];
        $keysGroup2 = [];

        if ($aggregatedTimeEntries['grouped_data'] !== null) {
            foreach ($aggregatedTimeEntries['grouped_data'] as $group1) {
                $keysGroup1[] = $group1['key'];
                if ($group1['grouped_data'] !== null) {
                    foreach ($group1['grouped_data'] as $group2) {
                        $keysGroup2[] = $group2['key'];
                    }
                }
            }
        }

        $descriptionMapGroup1 = $group1Type !== null ? $this->loadDescriptorsMap($keysGroup1, $group1Type) : [];
        $descriptionMapGroup2 = $group2Type !== null ? $this->loadDescriptorsMap($keysGroup2, $group2Type) : [];

        if ($aggregatedTimeEntries['grouped_data'] !== null) {
            foreach ($aggregatedTimeEntries['grouped_data'] as $keyGroup1 => $group1) {
                $aggregatedTimeEntries['grouped_data'][$keyGroup1]['description'] = $group1['key'] !== null ? ($descriptionMapGroup1[$group1['key']]['description'] ?? null) : null;
                $aggregatedTimeEntries['grouped_data'][$keyGroup1]['color'] = $group1['key'] !== null ? ($descriptionMapGroup1[$group1['key']]['color'] ?? null) : null;
                if ($aggregatedTimeEntries['grouped_data'][$keyGroup1]['grouped_data'] !== null) {
                    foreach ($aggregatedTimeEntries['grouped_data'][$keyGroup1]['grouped_data'] as $keyGroup2 => $group2) {
                        $aggregatedTimeEntries['grouped_data'][$keyGroup1]['grouped_data'][$keyGroup2]['description'] = $group2['key'] !== null ? ($descriptionMapGroup2[$group2['key']]['description'] ?? null) : null;
                        $aggregatedTimeEntries['grouped_data'][$keyGroup1]['grouped_data'][$keyGroup2]['color'] = $group2['key'] !== null ? ($descriptionMapGroup2[$group2['key']]['color'] ?? null) : null;
                    }
                }
            }
        }

        /**
         * @var array{
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
         *  } $aggregatedTimeEntries
         */

        return $aggregatedTimeEntries;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, array{
     *     description: string,
     *     color: string|null
     * }>
     */
    private function loadDescriptorsMap(array $keys, TimeEntryAggregationType $type): array
    {
        $descriptorMap = [];
        if ($type === TimeEntryAggregationType::Client) {
            $clients = Client::query()
                ->whereIn('id', $keys)
                ->select('id', 'name')
                ->get();
            foreach ($clients as $client) {
                $descriptorMap[$client->id] = [
                    'description' => $client->name,
                    'color' => null,
                ];
            }
        } elseif ($type === TimeEntryAggregationType::User) {
            $users = User::query()
                ->whereIn('id', $keys)
                ->select('id', 'name')
                ->get();
            foreach ($users as $user) {
                $descriptorMap[$user->id] = [
                    'description' => $user->name,
                    'color' => null,
                ];
            }
        } elseif ($type === TimeEntryAggregationType::Project) {
            $projects = Project::query()
                ->whereIn('id', $keys)
                ->select('id', 'name', 'color')
                ->get();
            foreach ($projects as $project) {
                $descriptorMap[$project->id] = [
                    'description' => $project->name,
                    'color' => $project->color,
                ];
            }
        } elseif ($type === TimeEntryAggregationType::Task) {
            $tasks = Task::query()
                ->whereIn('id', $keys)
                ->select('id', 'name')
                ->get();
            foreach ($tasks as $task) {
                $descriptorMap[$task->id] = [
                    'description' => $task->name,
                    'color' => null,
                ];
            }
        } elseif ($type === TimeEntryAggregationType::Description) {
            foreach ($keys as $key) {
                $descriptorMap[$key] = [
                    'description' => $key,
                    'color' => null,
                ];
            }
        } elseif ($type === TimeEntryAggregationType::Billable) {
            foreach ($keys as $key) {
                $descriptorMap[$key] = [
                    'description' => $key === '0' ? 'Non-billable' : 'Billable',
                    'color' => null,
                ];
            }
        }

        return $descriptorMap;
    }

    /**
     * @param array<array{
     *            key: string|null,
     *            seconds: int,
     *            cost: int|null,
     *            grouped_type: string|null,
     *            grouped_data: null|array<array{
     *                key: string|null,
     *                seconds: int,
     *                cost: int|null,
     *                grouped_type: null|mixed,
     *                grouped_data: null|mixed
     *            }>
     *        }> $data
     * @return array<array{
     *            key: string|null,
     *            seconds: int,
     *            cost: int|null,
     *            grouped_type: string|null,
     *            grouped_data: null|array<array{
     *                key: string|null,
     *                seconds: int,
     *                cost: int|null,
     *                grouped_type: null|mixed,
     *                grouped_data: null|mixed
     *            }>
     *        }>
     */
    public function fillGapsInTimeGroups(array $data, TimeEntryAggregationType $groupType, ?TimeEntryAggregationType $subGroupType, string $timezone, Weekday $startOfWeek, Carbon $start, Carbon $end): array
    {
        $interval = $groupType->toInterval();
        if ($interval === null) {
            foreach ($data as $key => $item) {
                $data[$key]['grouped_data'] = $this->fillGapsInTimeGroups(
                    $item['grouped_data'],
                    $subGroupType,
                    null,
                    $timezone,
                    $startOfWeek,
                    $start,
                    $end
                );
            }

            return $data;
        } else {
            $format = match ($interval) {
                TimeEntryAggregationTypeInterval::Day, TimeEntryAggregationTypeInterval::Week => 'Y-m-d',
                TimeEntryAggregationTypeInterval::Month => 'Y-m',
                TimeEntryAggregationTypeInterval::Year => 'Y',
            };
            $slots = $this->timeSlotsBetween($start, $end, $timezone, $startOfWeek, $interval, $format);
            $foundEntries = [];
            $filledData = [];
            foreach ($slots as $slot) {
                $foundDataSet = null;
                foreach ($data as $item) {
                    if ($item['key'] === $slot) {
                        $foundDataSet = $item;
                        $foundEntries[] = $item['key'];
                        break;
                    }
                }
                if ($foundDataSet !== null) {
                    $filledData[] = [
                        'key' => $slot,
                        'seconds' => $foundDataSet['seconds'],
                        'cost' => $foundDataSet['cost'],
                        'grouped_type' => $subGroupType?->value,
                        'grouped_data' => $subGroupType === null
                            ? null
                            : $this->fillGapsInTimeGroups(
                                $foundDataSet['grouped_data'],
                                $subGroupType,
                                null,
                                $timezone,
                                $startOfWeek,
                                $start,
                                $end
                            ),
                    ];
                } else {
                    $filledData[] = [
                        'key' => $slot,
                        'seconds' => 0,
                        'cost' => 0,
                        'grouped_type' => $subGroupType?->value,
                        'grouped_data' => $subGroupType === null ? null : [],
                    ];
                }
            }

            if (count($foundEntries) !== count($data)) {
                foreach ($data as $item) {
                    if (! in_array($item['key'], $foundEntries, true)) {
                        Log::error('Problem with filling gaps in time groups', [
                            'item' => $item,
                        ]);
                    }
                }
            }

            return $filledData;
        }
    }

    private function getGroupByQuery(TimeEntryAggregationType $group, string $timezone, Weekday $startOfWeek): string
    {
        $timezoneShift = app(TimezoneService::class)->getShiftFromUtc(new CarbonTimeZone($timezone));
        if ($timezoneShift > 0) {
            $dateWithTimeZone = 'start + INTERVAL \''.$timezoneShift.' second\'';
        } elseif ($timezoneShift < 0) {
            $dateWithTimeZone = 'start - INTERVAL \''.abs($timezoneShift).' second\'';
        } else {
            $dateWithTimeZone = 'start';
        }
        $startOfWeek = Carbon::now()->setTimezone($timezone)->startOfWeek($startOfWeek->carbonWeekDay())->toDateTimeString();
        if ($group === TimeEntryAggregationType::Day) {
            return 'date('.$dateWithTimeZone.')';
        } elseif ($group === TimeEntryAggregationType::Week) {
            return "to_char(date_bin('7 days', ".$dateWithTimeZone.", timestamp '".$startOfWeek."'), 'YYYY-MM-DD')";
        } elseif ($group === TimeEntryAggregationType::Month) {
            return 'to_char('.$dateWithTimeZone.', \'YYYY-MM\')';
        } elseif ($group === TimeEntryAggregationType::Year) {
            return 'to_char('.$dateWithTimeZone.', \'YYYY\')';
        } elseif ($group === TimeEntryAggregationType::User) {
            return 'user_id';
        } elseif ($group === TimeEntryAggregationType::Project) {
            return 'project_id';
        } elseif ($group === TimeEntryAggregationType::Task) {
            return 'task_id';
        } elseif ($group === TimeEntryAggregationType::Client) {
            return 'client_id';
        } elseif ($group === TimeEntryAggregationType::Billable) {
            return 'billable';
        } elseif ($group === TimeEntryAggregationType::Description) {
            return 'description';
        }
    }

    /**
     * @return Collection<int, string>
     */
    public function timeSlotsBetween(Carbon $start, Carbon $end, string $timezone, Weekday $startOfWeek, TimeEntryAggregationTypeInterval $interval, string $format): Collection
    {
        if ($start->gt($end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }
        $slots = new Collection;
        $current = $start->copy()->timezone($timezone);
        if ($interval === TimeEntryAggregationTypeInterval::Day) {
            $current->startOfDay();
        } elseif ($interval === TimeEntryAggregationTypeInterval::Week) {
            $current->startOfWeek($startOfWeek->carbonWeekDay());
        } elseif ($interval === TimeEntryAggregationTypeInterval::Month) {
            $current->startOfMonth();
        } elseif ($interval === TimeEntryAggregationTypeInterval::Year) {
            $current->startOfYear();
        } else {
            throw new \InvalidArgumentException('Invalid interval');
        }

        while ($current->lt($end)) {
            $slots->push($current->format($format));
            if ($interval === TimeEntryAggregationTypeInterval::Day) {
                $current->addDay();
            } elseif ($interval === TimeEntryAggregationTypeInterval::Week) {
                $current->addWeek();
            } elseif ($interval === TimeEntryAggregationTypeInterval::Month) {
                $current->addMonth();
            } elseif ($interval === TimeEntryAggregationTypeInterval::Year) {
                $current->addYear();
            }
        }

        return $slots;
    }
}
