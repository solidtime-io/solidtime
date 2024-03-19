<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<int, string>
     */
    private function lastDays(int $days, CarbonTimeZone $timeZone): array
    {
        $result = [];
        $date = Carbon::now($timeZone);
        for ($i = 0; $i < $days; $i++) {
            $result[] = $date->format('Y-m-d');
            $date = $date->subDay();
        }

        return $result;
    }

    /**
     * Get the daily tracked hours for the user
     * First value: date
     * Second value: seconds
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public function getDailyTrackedHours(User $user, int $days): array
    {
        $timezone = new CarbonTimeZone($user->timezone);
        $timezoneShift = $timezone->getOffset(new \DateTime('now', new \DateTimeZone('UTC')));

        if ($timezoneShift > 0) {
            $dateWithTimeZone = 'start + INTERVAL \''.$timezoneShift.' second\'';
        } elseif ($timezoneShift < 0) {
            $dateWithTimeZone = 'start - INTERVAL \''.abs($timezoneShift).' second\'';
        } else {
            $dateWithTimeZone = 'start';
        }

        $resultDb = TimeEntry::query()
            ->select(DB::raw('DATE('.$dateWithTimeZone.') as date, round(sum(extract(epoch from (coalesce("end", now()) - start)))) as value'))
            ->where('user_id', '=', $user->id)
            ->groupBy(DB::raw('DATE('.$dateWithTimeZone.')'))
            ->orderBy('date')
            ->get()
            ->pluck('value', 'date');

        $result = [];
        $lastDays = $this->lastDays($days, $timezone);

        foreach ($lastDays as $day) {
            $result[] = [$day, (int) ($resultDb->get($day) ?? 0)];
        }

        return $result;
    }

    /**
     * Statistics for the current week starting at Monday / Sunday
     *
     * @return array<int, array{date: string, duration: int}>
     */
    public function getWeeklyHistory(User $user): array
    {
        return [
            [
                'date' => '2024-02-26',
                'duration' => 3600,
            ],
            [
                'date' => '2024-02-27',
                'duration' => 2000,
            ],
            [
                'date' => '2024-02-28',
                'duration' => 4000,
            ],
            [
                'date' => '2024-02-29',
                'duration' => 3000,
            ],
            [
                'date' => '2024-03-01',
                'duration' => 5000,
            ],
            [
                'date' => '2024-03-02',
                'duration' => 3000,
            ],
            [
                'date' => '2024-03-03',
                'duration' => 2000,
            ],
        ];
    }
}
