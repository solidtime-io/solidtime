<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends CsvExport<TimeEntry>
 */
class TimeEntriesDetailedCsvExport extends CsvExport
{
    public const array HEADER = [
        'Description',
        'Task',
        'Project',
        'Client',
        'User',
        'Start',
        'End',
        'Duration',
        'Duration (decimal)',
        'Billable',
        'Tags',
    ];

    /**
     * @param  TimeEntry  $model
     */
    public function mapRow(Model $model): array
    {
        $duration = $model->getDuration();

        return [
            'Description' => $model->description,
            'Task' => $model->task?->name,
            'Project' => $model->project?->name,
            'Client' => $model->client?->name,
            'User' => $model->user->name,
            'Start' => $model->start->format('Y-m-d H:i:s'),
            'End' => $model->end?->format('Y-m-d H:i:s'),
            'Duration' => $duration !== null ? (int) floor($duration->totalHours).':'.$duration->format('%I:%S') : null,
            'Duration (decimal)' => $duration?->totalHours,
            'Billable' => $model->billable ? 'Yes' : 'No',
            'Tags' => $model->tagsRelation->pluck('name')->implode(', '),
        ];
    }
}
