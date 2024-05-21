<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TimezoneService
{
    /**
     * @return array<string>
     */
    public function getTimezones(): array
    {
        return CarbonTimeZone::listIdentifiers();
    }

    public function getTimezoneFromUser(User $user): CarbonTimeZone
    {
        try {
            return new CarbonTimeZone($user->timezone);
        } catch (\Exception $e) {
            Log::error('User has a invalid timezone', [
                'user_id' => $user->getKey(),
                'timezone' => $user->timezone,
            ]);

            return new CarbonTimeZone('UTC');
        }
    }

    /**
     * @return array<string, string>
     */
    public function getSelectOptions(): array
    {
        $tzlist = $this->getTimezones();
        $options = [];
        foreach ($tzlist as $tz) {
            $options[$tz] = $tz;
        }

        return $options;
    }

    public function isValid(string $timezone): bool
    {
        return in_array($timezone, $this->getTimezones(), true);
    }

    public function getShiftFromUtc(CarbonTimeZone $timeZone): int
    {
        return $timeZone->getOffset(Carbon::now());
    }
}
