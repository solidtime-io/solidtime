<?php

declare(strict_types=1);

namespace App\Service;

use Carbon\CarbonInterval;

class IntervalService
{
    public function format(CarbonInterval $interval): string
    {
        $interval->cascade();

        return ((int) floor($interval->totalHours)).':'.$interval->format('%I:%S');
    }
}
