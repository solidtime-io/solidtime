<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Enums\ExportFormat;
use App\Enums\TimeEntryAggregationType;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class TimeEntriesReportExport implements FromView, ShouldAutoSize, WithCustomCsvSettings
{
    use Exportable;

    /**
     * @var array{
     *        grouped_type: string|null,
     *        grouped_data: null|array<array{
     *            key: string|null,
     *            seconds: int,
     *            cost: int|null,
     *            grouped_type: string|null,
     *            grouped_data: null|array<array{
     *                key: string|null,
     *                seconds: int,
     *                cost: int|null,
     *                grouped_type: null,
     *                grouped_data: null
     *            }>
     *        }>,
     *        seconds: int,
     *        cost: int|null
     *  }
     */
    private array $data;

    private ExportFormat $exportFormat;

    private string $currency;

    private TimeEntryAggregationType $group;

    private TimeEntryAggregationType $subGroup;

    /**
     * @param array{
     *         grouped_type: string|null,
     *         grouped_data: null|array<array{
     *             key: string|null,
     *             seconds: int,
     *             cost: int|null,
     *             grouped_type: string|null,
     *             grouped_data: null|array<array{
     *                 key: string|null,
     *                 seconds: int,
     *                 cost: int|null,
     *                 grouped_type: null,
     *                 grouped_data: null
     *             }>
     *         }>,
     *         seconds: int,
     *         cost: int|null
     *   } $data
     */
    public function __construct(array $data, ExportFormat $exportFormat, string $currency, TimeEntryAggregationType $group, TimeEntryAggregationType $subGroup)
    {
        $this->data = $data;
        $this->exportFormat = $exportFormat;
        $this->currency = $currency;
        $this->group = $group;
        $this->subGroup = $subGroup;
    }

    public function view(): View
    {
        return view('reports.time-entry-aggregate.spreadsheet', [
            'data' => $this->data,
            'currency' => $this->currency,
            'group' => $this->group,
            'subGroup' => $this->subGroup,
            'exportFormat' => $this->exportFormat,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '',
        ];
    }
}
