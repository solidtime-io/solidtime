<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\TimeEntryAggregationType;
use App\Enums\TimeEntryAggregationTypeInterval;
use App\Enums\Weekday;
use App\Models\TimeEntry;
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
     *           cost: int,
     *           grouped_type: string|null,
     *           grouped_data: null|array<array{
     *               key: string|null,
     *               seconds: int,
     *               cost: int,
     *               grouped_type: null,
     *               grouped_data: null
     *           }>
     *       }>,
     *       seconds: int,
     *       cost: int
     * }
     */
    public function getAggregatedTimeEntries(Builder $timeEntriesQuery, ?TimeEntryAggregationType $group1Type, ?TimeEntryAggregationType $group2Type, string $timezone, Weekday $startOfWeek, bool $fillGapsInTimeGroups, ?Carbon $start, ?Carbon $end): array
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
                            'cost' => (int) $aggregate->get(0)->cost,
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
                    'cost' => $group2ResponseCost,
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
            'cost' => $group1ResponseCost,
            'grouped_type' => $group1Type?->value,
            'grouped_data' => $group1Response,
        ];
    }

    /**
     * @param array<array{
     *            key: string|null,
     *            seconds: int,
     *            cost: int,
     *            grouped_type: string|null,
     *            grouped_data: null|array<array{
     *                key: string|null,
     *                seconds: int,
     *                cost: int,
     *                grouped_type: null|mixed,
     *                grouped_data: null|mixed
     *            }>
     *        }> $data
     * @return array<array{
     *            key: string|null,
     *            seconds: int,
     *            cost: int,
     *            grouped_type: string|null,
     *            grouped_data: null|array<array{
     *                key: string|null,
     *                seconds: int,
     *                cost: int,
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
        $slots = new Collection();
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
