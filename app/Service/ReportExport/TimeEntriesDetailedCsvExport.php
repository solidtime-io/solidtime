<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Models\TimeEntry;
use App\Service\IntervalService;
use Illuminate\Database\Eloquent\Builder;
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

    protected const string CARBON_FORMAT = 'Y-m-d H:i:s';

    private string $timezone;

    public function __construct(string $disk, string $folderPath, string $filename, Builder $builder, int $chunk, string $timezone)
    {
        parent::__construct($disk, $folderPath, $filename, $builder, $chunk);

        $this->timezone = $timezone;
    }

    /**
     * @param  TimeEntry  $model
     */
    public function mapRow(Model $model): array
    {
        $interval = app(IntervalService::class);
        $duration = $model->getDuration();

        return [
            'Description' => $model->description,
            'Task' => $model->task?->name,
            'Project' => $model->project?->name,
            'Client' => $model->client?->name,
            'User' => $model->user->name,
            'Start' => $model->start->timezone($this->timezone),
            'End' => $model->end->timezone($this->timezone),
            'Duration' => $duration !== null ? $interval->format($model->getDuration()) : null,
            'Duration (decimal)' => $duration?->totalHours,
            'Billable' => $model->billable ? 'Yes' : 'No',
            'Tags' => $model->tagsRelation->pluck('name')->implode(', '),
        ];
    }
}
