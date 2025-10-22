<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum TimeEntryAggregationType: string
{
    use LaravelEnumHelper;

    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case User = 'user';
    case Project = 'project';
    case Task = 'task';
    case Client = 'client';
    case Billable = 'billable';
    case Description = 'description';
    case Tag = 'tag';
    case Milestone = 'milestone';

    public static function fromInterval(TimeEntryAggregationTypeInterval $timeEntryAggregationTypeInterval): TimeEntryAggregationType
    {
        return match ($timeEntryAggregationTypeInterval) {
            TimeEntryAggregationTypeInterval::Day => TimeEntryAggregationType::Day,
            TimeEntryAggregationTypeInterval::Week => TimeEntryAggregationType::Week,
            TimeEntryAggregationTypeInterval::Month => TimeEntryAggregationType::Month,
            TimeEntryAggregationTypeInterval::Year => TimeEntryAggregationType::Year,
        };
    }

    public function toInterval(): ?TimeEntryAggregationTypeInterval
    {
        return match ($this) {
            TimeEntryAggregationType::Day => TimeEntryAggregationTypeInterval::Day,
            TimeEntryAggregationType::Week => TimeEntryAggregationTypeInterval::Week,
            TimeEntryAggregationType::Month => TimeEntryAggregationTypeInterval::Month,
            TimeEntryAggregationType::Year => TimeEntryAggregationTypeInterval::Year,
            default => null
        };
    }
}
