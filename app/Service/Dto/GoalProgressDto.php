<?php

declare(strict_types=1);

namespace App\Service\Dto;

use App\Enums\GoalStatus;
use Illuminate\Support\Carbon;

readonly class GoalProgressDto
{
    /**
     * @param  Carbon  $periodStart  Start of the period in UTC (inclusive)
     * @param  Carbon  $periodEnd  End of the period in UTC (exclusive)
     */
    public function __construct(
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public int $trackedSeconds,
        public GoalStatus $status,
    ) {}
}
