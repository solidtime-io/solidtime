<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Weekday;
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
        if ($value2 === false || $value1 === false) {
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
    public function getDailyTrackedHours(User $user, int $days): array
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
            ->where('user_id', '=', $user->id)
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
    public function getWeeklyHistory(User $user): array
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
            ->where('user_id', '=', $user->id)
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
}
