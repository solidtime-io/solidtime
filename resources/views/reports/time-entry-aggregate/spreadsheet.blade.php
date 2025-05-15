@use('App\Enums\ExportFormat')
@use('Brick\Math\BigDecimal')
@use('PhpOffice\PhpSpreadsheet\Cell\DataType')
@use('PhpOffice\PhpSpreadsheet\Style\NumberFormat')
@use('Carbon\CarbonInterval')
@use('App\Enums\TimeEntryAggregationType')
@inject('interval', 'App\Service\IntervalService')
<table>
    <thead>
    <tr>
        <th style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            {{ $group->description() }}
        </th>
        <th style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            {{ $subGroup->description() }}
        </th>
        <th style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            Duration
        </th>
        <th style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            Duration (decimal)
        </th>
        <th style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            Amount ({{ Str::upper($currency) }})
        </th>
    </tr>
    </thead>
    <tbody>
    @php
        $counter = 1;
        $totalDuration = 0;
        $totalCost = 0;
    @endphp
    @foreach($data['grouped_data'] as $group1Entry)
        @foreach($group1Entry['grouped_data'] as $group2Entry)
            @php
                $duration = CarbonInterval::seconds($group2Entry['seconds']);
            @endphp
            <tr>
                @if($exportFormat === ExportFormat::ODS || $exportFormat === ExportFormat::CSV)
                    @if ($group === TimeEntryAggregationType::Billable)
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group1Entry['key'] ? 'Yes' : 'No' }}
                        </td>
                    @else
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group1Entry['description'] ?? $group1Entry['key'] ?? '-' }}
                        </td>
                    @endif
                    @if ($subGroup === TimeEntryAggregationType::Billable)
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group2Entry['key'] ? 'Yes' : 'No' }}
                        </td>
                    @else
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                        </td>
                    @endif
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ $interval->format($duration) }}
                    </td>
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ round($duration->totalHours, 2) }}
                    </td>
                    @if($showBillableRate)
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ round(BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->toFloat(), 2) }}
                    </td>
                    @endif
                @else
                    @if ($group === TimeEntryAggregationType::Billable)
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group1Entry['key'] ? 'Yes' : 'No' }}
                        </td>
                    @else
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group1Entry['description'] ?? $group1Entry['key'] ?? '-' }}
                        </td>
                    @endif
                    @if ($subGroup === TimeEntryAggregationType::Billable)
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group2Entry['key'] ? 'Yes' : 'No' }}
                        </td>
                    @else
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                        </td>
                    @endif
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_NUMERIC }}"
                        data-format="[hh]:mm:ss">
                        {{ $duration->totalDays }}
                    </td>
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_NUMERIC }}"
                        data-format="{{ NumberFormat::FORMAT_NUMBER_00 }}">
                        {{ $duration->totalHours }}
                    </td>
                    @if($showBillableRate)
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_NUMERIC }}"
                        data-format="{{ NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1 }}">
                        {{ BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->__toString() }}
                    </td>
                    @endif
                @endif
            </tr>
            @php
                ++$counter;
                $totalDuration += $group2Entry['seconds'];
                if ($showBillableRate) {
                    $totalCost += $group2Entry['cost'];
                }
            @endphp
        @endforeach
    @endforeach
    @php
        $totalDurationInterval = CarbonInterval::seconds($totalDuration);
    @endphp
    <tr style="border: 1px solid black;">
        <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}"></td>
        <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
            Total
        </td>
        @if($exportFormat === ExportFormat::ODS || $exportFormat === ExportFormat::CSV)
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
                {{ $interval->format($totalDurationInterval) }}
            </td>
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
                {{ round($totalDurationInterval->totalHours, 2) }}
            </td>
            @if($showBillableRate)
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
                {{ round(BigDecimal::ofUnscaledValue($totalCost, 2)->toFloat(), 2) }}
            </td>
            @endif
        @else
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="[hh]:mm:ss">
                @if($counter > 1)
                    =SUM(C2:C{{ $counter }})
                @else
                    =0
                @endif
            </td>
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="{{ NumberFormat::FORMAT_NUMBER_00 }}">
                @if($counter > 1)
                    =SUM(D2:D{{ $counter }})
                @else
                    =0
                @endif
            </td>
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="{{ NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1 }}">
                @if($counter > 1)
                    =SUM(E2:E{{ $counter }})
                @else
                    =0
                @endif
            </td>
        @endif
    </tr>
    </tbody>
</table>
