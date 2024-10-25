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
                <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                    @if ($group === TimeEntryAggregationType::Billable)
                        {{ $group1Entry['key'] ? 'Yes' : 'No' }}
                    @else
                        {{ $group1Entry['description'] ?? $group1Entry['key'] ?? '-' }}
                    @endif
                </td>
                @if($exportFormat === ExportFormat::ODS || $exportFormat === ExportFormat::CSV)
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        @if ($subGroup === TimeEntryAggregationType::Billable)
                            {{ $group2Entry['key'] ? 'Yes' : 'No' }}
                        @else
                            {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                        @endif
                    </td>
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ $interval->format($duration) }}
                    </td>
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ round($duration->totalHours, 2) }}
                    </td>
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                        {{ round(BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->toFloat(), 2) }}
                    </td>
                @else
                    @if ($subGroup === TimeEntryAggregationType::Billable)
                        <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_STRING }}">
                            {{ $group1Entry['key'] ? 'Yes' : 'No' }}
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
                    <td style="border: 1px solid black;" data-type="{{ DataType::TYPE_NUMERIC }}"
                        data-format="{{ NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1 }}">
                        {{ BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->__toString() }}
                    </td>
                @endif
            </tr>
            @php
                ++$counter;
                $totalDuration += $group2Entry['seconds'];
                $totalCost += $group2Entry['cost'];
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
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_STRING }}">
                {{ round(BigDecimal::ofUnscaledValue($totalCost, 2)->toFloat(), 2) }}
            </td>
        @else
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="[hh]:mm:ss">
                =SUM(C2:C{{ $counter }})
            </td>
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="{{ NumberFormat::FORMAT_NUMBER_00 }}">
                =SUM(D2:D{{ $counter }})
            </td>
            <td style="border: 1px solid black; font-weight: bold;" data-type="{{ DataType::TYPE_FORMULA }}"
                data-format="{{ NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1 }}">
                =SUM(E2:E{{ $counter }})
            </td>
        @endif
    </tr>
    </tbody>
</table>
