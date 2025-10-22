<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Enums\ExportFormat;
use App\Models\TimeEntry;
use App\Service\LocalizationService;
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

    private string $timezone;

    private LocalizationService $localizationService;

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder, ExportFormat $exportFormat, string $timezone, LocalizationService $localizationService)
    {
        $this->builder = $builder;
        $this->exportFormat = $exportFormat;
        $this->timezone = $timezone;
        $this->localizationService = $localizationService;
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
        // Preserve original column order/shape unless planner is enabled
        if (!config('planner.enabled')) {
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
        return [
            'Description',
            'Milestone',
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
        $duration = $model->getDuration();

        if ($this->exportFormat === ExportFormat::XLSX) {
            if (!config('planner.enabled')) {
                return [
                    $model->description,
                    $model->task?->name,
                    $model->project?->name,
                    $model->client?->name,
                    $model->user->name,
                    Date::dateTimeToExcel($model->start->timezone($this->timezone)),
                    $model->end !== null ? Date::dateTimeToExcel($model->end->timezone($this->timezone)) : null,
                    $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                    $duration?->totalHours,
                    $model->billable ? 'Yes' : 'No',
                    $model->tagsRelation->pluck('name')->implode(', '),
                ];
            }
            return [
                $model->description,
                $model->milestoneTask?->name,
                $model->task?->name,
                $model->project?->name,
                $model->client?->name,
                $model->user->name,
                Date::dateTimeToExcel($model->start->timezone($this->timezone)),
                $model->end !== null ? Date::dateTimeToExcel($model->end->timezone($this->timezone)) : null,
                $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                $duration?->totalHours,
                $model->billable ? 'Yes' : 'No',
                $model->tagsRelation->pluck('name')->implode(', '),
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            if (!config('planner.enabled')) {
                return [
                    $model->description,
                    $model->task?->name,
                    $model->project?->name,
                    $model->client?->name,
                    $model->user->name,
                    $model->start->timezone($this->timezone)->format('Y-m-d H:i:s'),
                    $model->end?->timezone($this->timezone)?->format('Y-m-d H:i:s'),
                    $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                    $duration?->totalHours,
                    $model->billable ? 'Yes' : 'No',
                    $model->tagsRelation->pluck('name')->implode(', '),
                ];
            }
            return [
                $model->description,
                $model->milestoneTask?->name,
                $model->task?->name,
                $model->project?->name,
                $model->client?->name,
                $model->user->name,
                $model->start->timezone($this->timezone)->format('Y-m-d H:i:s'),
                $model->end?->timezone($this->timezone)?->format('Y-m-d H:i:s'),
                $duration !== null ? $this->localizationService->formatInterval($duration) : null,
                $duration?->totalHours,
                $model->billable ? 'Yes' : 'No',
                $model->tagsRelation->pluck('name')->implode(', '),
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }
}
