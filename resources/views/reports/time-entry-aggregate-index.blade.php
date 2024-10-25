@use('Brick\Math\BigDecimal')
@use('PhpOffice\PhpSpreadsheet\Cell\DataType')
@use('Carbon\CarbonInterval')
@inject('interval', 'App\Service\IntervalService')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Report</title>
    <style>
        body {
            font-family: "Open Sans", sans-serif;
        }
        table {
            font-size: 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
</head>
<body>
    <h1>Report</h1>

    <div>
        <span>{{ $start->format('Y-m-d') }} - {{ $end->format('Y-m-d') }}</span><br><br>
    </div>

    <div>
        <span>Duration: {{ $interval->format(CarbonInterval::seconds($aggregatedData['seconds'])) }}</span><br>
        <span>Total cost: {{ round(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->toFloat(), 2) }}</span><br>
    </div>

    <div id="main-chart" style="width: 800px; height:400px;"></div>

    @foreach($aggregatedData['grouped_data'] as $group1Entry)
        <h2>{{ $group->description() }}: {{ $group1Entry['description'] ?? $group1Entry['key'] ?? '-' }}</h2>

        <table>
            <thead>
            <tr>
                <th>
                    {{ $subGroup->description() }}
                </th>
                <th>
                    Duration
                </th>
                <th>
                    Duration (decimal)
                </th>
                <th>
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
            @foreach($group1Entry['grouped_data'] as $group2Entry)
                @php
                    $duration = CarbonInterval::seconds($group2Entry['seconds']);
                @endphp
                <tr>
                    <td>
                        {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                    </td>
                    <td>
                        {{ $interval->format($duration) }}
                    </td>
                    <td>
                        {{ round($duration->totalHours, 2) }}
                    </td>
                    <td>
                        {{ round(BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->toFloat(), 2) }}
                    </td>
                </tr>
                @php
                    $totalDuration += $group2Entry['seconds'];
                    $totalCost += $group2Entry['cost'];
                @endphp
            @endforeach
            </tbody>
        </table>
    @endforeach

    <script>
        // Initialize the echarts instance based on the prepared dom
        let element = document.getElementById('main-chart');
        let myChart = echarts.init(element, null, {
            renderer: 'svg'
        });

        // Specify the configuration items and data for the chart
        let option = {
            tooltip: {},
            xAxis: {
                data: ['{!! collect($dataHistoryChart['grouped_data'])->pluck('key')->implode("', '") !!}'],
                rotate: 0
            },
            yAxis: {
                minInterval: 1,
                axisLabel: {
                    formatter: function (value, index) {
                        let totalSeconds = value;
                        let hours = Math.floor(totalSeconds / 3600);
                        totalSeconds %= 3600;
                        let minutes = Math.floor(totalSeconds / 60);
                        let seconds = totalSeconds % 60;
                        return hours + ':' + minutes + ':' + seconds;
                    }
                }
            },
            series: [
                {
                    name: 'time',
                    type: 'bar',
                    data: [{!! collect($dataHistoryChart['grouped_data'])->pluck('seconds')->implode(', ') !!}],
                }
            ]
        };

        // Display the chart using the configuration items and data just specified.
        myChart.setOption(option);
    </script>
</body>
</html>
