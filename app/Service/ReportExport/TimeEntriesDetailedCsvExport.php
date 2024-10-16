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
        'id',
        'user_id',
        'project_id',
        'task_id',
        'start_time',
        'end_time',
        'duration',
        'description',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @param  TimeEntry  $model
     */
    public function mapRow(Model $model): array
    {
        return [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'project_id' => $model->project_id,
            'task_id' => $model->task_id,
            'start_time' => $model->start,
            'end_time' => $model->end,
            'description' => $model->description,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];
    }
}
