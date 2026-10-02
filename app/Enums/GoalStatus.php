<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum GoalStatus: string
{
    use LaravelEnumHelper;

    /**
     * "At least" goal, the target has not been reached yet in the current period.
     */
    case InProgress = 'in_progress';

    /**
     * "At least" goal, the target has been reached in the current period.
     */
    case Achieved = 'achieved';

    /**
     * "Less than" goal, the tracked time is still below the limit.
     */
    case OnTrack = 'on_track';

    /**
     * "Less than" goal, the tracked time reached or exceeded the limit.
     */
    case Exceeded = 'exceeded';
}
