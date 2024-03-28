<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private TimezoneService $timezoneService;

    public function __construct(TimezoneService $timezoneService)
    {
        $this->timezoneService = $timezoneService;
    }

    /**
     * @return Collection<int, string>
     */
    private function lastDays(int $days, CarbonTimeZone $timeZone): Collection
    {
        $result = new Collection();
        $date = Carbon::now($timeZone)->subDays($days);
        for ($i = 0; $i < $days; $i++) {
            $date->addDay();
            $result->push($date->format('Y-m-d'));
        }

        return $result;
    }

    /**
     * @return Collection<int, string>
     */
    private function daysOfThisWeek(CarbonTimeZone $timeZone, Weekday $weekday): Collection
    {
        $result = new Collection();
        $date = Carbon::now($timeZone);
        $start = $date->startOfWeek($weekday->carbonWeekDay());
        for ($i = 0; $i < 7; $i++) {
            $result->push($start->format('Y-m-d'));
            $start->addDay();
        }

        return $result;
    }

    /**
     * @param  Collection<int, string>  $possibleDates
     * @param  Builder<TimeEntry>  $builder
     * @return Builder<TimeEntry>
     */
    private function constrainDateByPossibleDates(Builder $builder, Collection $possibleDates, CarbonTimeZone $timeZone): Builder
    {
        $value1 = Carbon::createFromFormat('Y-m-d', $possibleDates->first(), $timeZone);
        $value2 = Carbon::createFromFormat('Y-m-d', $possibleDates->last(), $timeZone);
        if ($value2 === null || $value1 === null) {
            throw new \RuntimeException('Provided date is not valid');
        }
        if ($value1->gt($value2)) {
            $last = $value1;
            $first = $value2;
        } else {
            $last = $value2;
            $first = $value1;
        }

        return $builder->whereBetween('start', [
            $first->startOfDay()->utc(),
            $last->endOfDay()->utc(),
        ]);
    }

    /**
     * Get the daily tracked hours for the user
     * First value: date
     * Second value: seconds
     *
     * @return array<int, array{date: string, duration: int}>
     */
    public function getDailyTrackedHours(User $user, Organization $organization, int $days): array
    {
        $timezone = $this->timezoneService->getTimezoneFromUser($user);
        $timezoneShift = $this->timezoneService->getShiftFromUtc($timezone);

        if ($timezoneShift > 0) {
            $dateWithTimeZone = 'start + INTERVAL \''.$timezoneShift.' second\'';
        } elseif ($timezoneShift < 0) {
            $dateWithTimeZone = 'start - INTERVAL \''.abs($timezoneShift).' second\'';
        } else {
            $dateWithTimeZone = 'start';
        }

        $possibleDays = $this->lastDays($days, $timezone);

        $query = TimeEntry::query()
            ->select(DB::raw('DATE('.$dateWithTimeZone.') as date, round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate'))
            ->where('user_id', '=', $user->getKey())
            ->where('organization_id', '=', $organization->getKey())
            ->groupBy(DB::raw('DATE('.$dateWithTimeZone.')'))
            ->orderBy('date');

        $query = $this->constrainDateByPossibleDates($query, $possibleDays, $timezone);
        $resultDb = $query->get()
            ->pluck('aggregate', 'date');

        $result = [];

        foreach ($possibleDays as $possibleDay) {
            $result[] = [
                'date' => $possibleDay,
                'duration' => (int) ($resultDb->get($possibleDay) ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Statistics for the current week starting at weekday of users preference
     *
     * @return array<int, array{date: string, duration: int}>
     */
    public function getWeeklyHistory(User $user, Organization $organization): array
    {
        $timezone = $this->timezoneService->getTimezoneFromUser($user);
        $timezoneShift = $this->timezoneService->getShiftFromUtc($timezone);
        if ($timezoneShift > 0) {
            $dateWithTimeZone = 'start + INTERVAL \''.$timezoneShift.' second\'';
        } elseif ($timezoneShift < 0) {
            $dateWithTimeZone = 'start - INTERVAL \''.abs($timezoneShift).' second\'';
        } else {
            $dateWithTimeZone = 'start';
        }
        $possibleDays = $this->daysOfThisWeek($timezone, $user->week_start);

        $query = TimeEntry::query()
            ->select(DB::raw('DATE('.$dateWithTimeZone.') as date, round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate'))
            ->where('user_id', '=', $user->getKey())
            ->where('organization_id', '=', $organization->getKey())
            ->groupBy(DB::raw('DATE('.$dateWithTimeZone.')'))
            ->orderBy('date');

        $query = $this->constrainDateByPossibleDates($query, $possibleDays, $timezone);
        $resultDb = $query->get()
            ->pluck('aggregate', 'date');

        $result = [];

        foreach ($possibleDays as $possibleDay) {
            $result[] = [
                'date' => $possibleDay,
                'duration' => (int) ($resultDb->get($possibleDay) ?? 0),
            ];
        }

        return $result;
    }

    public function totalWeeklyTime(User $user, Organization $organization): int
    {
        $timezone = $this->timezoneService->getTimezoneFromUser($user);
        $possibleDays = $this->daysOfThisWeek($timezone, $user->week_start);

        $query = TimeEntry::query()
            ->select(DB::raw('round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate'))
            ->where('user_id', '=', $user->getKey())
            ->where('organization_id', '=', $organization->getKey());

        $query = $this->constrainDateByPossibleDates($query, $possibleDays, $timezone);
        /** @var Collection<int, object{aggregate: int}> $resultDb */
        $resultDb = $query->get();

        return (int) $resultDb->get(0)->aggregate;
    }

    public function totalWeeklyBillableTime(User $user, Organization $organization): int
    {
        $timezone = $this->timezoneService->getTimezoneFromUser($user);
        $possibleDays = $this->daysOfThisWeek($timezone, $user->week_start);

        $query = TimeEntry::query()
            ->select(DB::raw('round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate'))
            ->where('billable', '=', true)
            ->where('user_id', '=', $user->getKey())
            ->where('organization_id', '=', $organization->getKey());

        $query = $this->constrainDateByPossibleDates($query, $possibleDays, $timezone);
        /** @var Collection<int, object{aggregate: int}> $resultDb */
        $resultDb = $query->get();

        return (int) $resultDb->get(0)->aggregate;
    }

    /**
     * @return array{value: int, currency: string}
     */
    public function totalWeeklyBillableAmount(User $user, Organization $organization): array
    {
        $timezone = $this->timezoneService->getTimezoneFromUser($user);
        $possibleDays = $this->daysOfThisWeek($timezone, $user->week_start);

        $query = TimeEntry::query()
            ->select(DB::raw('
               round(
                    sum(
                        extract(epoch from (coalesce("end", now()) - start)) * (billable_rate::float/60/60)
                    )
               ) as aggregate'))
            ->where('billable', '=', true)
            ->whereNotNull('billable_rate')
            ->where('user_id', '=', $user->id);

        $query = $this->constrainDateByPossibleDates($query, $possibleDays, $timezone);
        /** @var Collection<int, object{aggregate: int}> $resultDb */
        $resultDb = $query->get();

        return [
            'value' => (int) $resultDb->get(0)->aggregate,
            'currency' => $organization->currency,
        ];
    }

    /**
     * @return array<int, array{value: int, name: string, color: string}>
     */
    public function weeklyProjectOverview(User $user, Organization $organization): array
    {
        return [
            [
                'value' => 120,
                'name' => 'Project 11',
                'color' => '#26a69a',
            ],
            [
                'value' => 200,
                'name' => 'Project 2',
                'color' => '#d4e157',
            ],
            [
                'value' => 150,
                'name' => 'Project 3',
                'color' => '#ff7043',
            ],
        ];
    }
}
