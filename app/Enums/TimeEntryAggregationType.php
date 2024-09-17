<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeEntryAggregationType: string
{
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
