<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<TimeEntry>
 */
class TimeEntriesDetailedExport implements FromQuery, WithCustomCsvSettings, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @var Builder<TimeEntry>
     */
    private Builder $builder;

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function query(): Builder
    {
        return $this->builder;
    }

    /**
     * @return array<string, string|bool>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'use_bom' => false,
            'output_encoding' => 'ISO-8859-1',
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Description',
            'Task',
            'Project',
            'Client',
            'User',
            'Start date',
            'Start time',
            'End date',
            'End time',
            'Duration',
            'Duration (decimal)',
            'Billable',
            'Tags',
        ];
    }

    /**
     * @param  TimeEntry  $model
     * @return array<int, string|float|null>
     */
    public function map($model): array
    {
        $duration = $model->getDuration();

        return [
            $model->description,
            $model->task?->name,
            $model->project?->name,
            $model->project?->client?->name,
            $model->user->name,
            $model->start->format('Y-m-d'),
            $model->start->format('H:i:s'),
            $model->end?->format('Y-m-d'),
            $model->end?->format('H:i:s'),
            $duration !== null ? (int) floor($duration->totalHours).':'.$duration->format('%I:%S') : null,
            $duration?->totalHours,
            $model->billable ? 'Yes' : 'No',
            $model->tagsRelation->pluck('name')->implode(', '),
        ];
    }
}
