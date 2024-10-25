<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Enums\ExportFormat;
use App\Models\TimeEntry;
use App\Service\IntervalService;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<TimeEntry>
 */
class TimeEntriesDetailedExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    /**
     * @var Builder<TimeEntry>
     */
    private Builder $builder;

    private ExportFormat $exportFormat;

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder, ExportFormat $exportFormat)
    {
        $this->builder = $builder;
        $this->exportFormat = $exportFormat;
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function query(): Builder
    {
        return $this->builder;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                'F' => 'yyyy-mm-dd hh:mm:ss',
                'G' => 'yyyy-mm-dd hh:mm:ss',
                'I' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                'I' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }

    }

    /**
     * @return array<int|string, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
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
            'Start',
            'End',
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
        $interval = app(IntervalService::class);
        $duration = $model->getDuration();

        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                $model->description,
                $model->task?->name,
                $model->project?->name,
                $model->client?->name,
                $model->user->name,
                Date::dateTimeToExcel($model->start),
                $model->end !== null ? Date::dateTimeToExcel($model->end) : null,
                $duration !== null ? $interval->format($duration) : null,
                $duration?->totalHours,
                $model->billable ? 'Yes' : 'No',
                $model->tagsRelation->pluck('name')->implode(', '),
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                $model->description,
                $model->task?->name,
                $model->project?->name,
                $model->client?->name,
                $model->user->name,
                $model->start->format('Y-m-d H:i:s'),
                $model->end?->format('Y-m-d H:i:s'),
                $duration !== null ? (int) floor($duration->totalHours).':'.$duration->format('%I:%S') : null,
                $duration?->totalHours,
                $model->billable ? 'Yes' : 'No',
                $model->tagsRelation->pluck('name')->implode(', '),
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }
}
