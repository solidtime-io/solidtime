<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum GoalComparison: string
{
    use LaravelEnumHelper;

    /**
     * The goal is reached when at least the target amount of time was tracked in the period.
     */
    case AtLeast = 'at_least';

    /**
     * The goal is reached when less than the target amount of time was tracked in the period.
     */
    case LessThan = 'less_than';
}
