<?php

declare(strict_types=1);

namespace App\Service;

use Carbon\CarbonTimeZone;
use DateTimeZone;

class TimezoneService
{
    /**
     * @return array<string>
     */
    public function getTimezones(): array
    {
        $tzlist = CarbonTimeZone::listIdentifiers(DateTimeZone::ALL);

        return $tzlist;
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
}
