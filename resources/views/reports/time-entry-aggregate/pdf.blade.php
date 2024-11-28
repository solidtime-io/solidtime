@use('Brick\Math\BigDecimal')
@use('Brick\Money\Money')
@use('PhpOffice\PhpSpreadsheet\Cell\DataType')
@use('Carbon\CarbonInterval')
@inject('interval', 'App\Service\IntervalService')
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        table {
            font-size: 10px;
        }

        table thead {
            background-color: #eee;
        }

        h1 {
            font-size: 35px;
            font-weight: bold;
        }

        h2 {
            font-size: 20px;
            font-weight: bold;
        }

        .range {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
    <script>
        window.status = 'processing';
    </script>
    <script src="echarts.min.js"></script>
</head>
<body>
<h1>Report</h1>

<hr>

<div class="range">
    <span>{{ $start->format('d.m.Y') }} - {{ $end->format('d.m.Y') }}</span><br><br>
</div>

<div class="properties">
    <span>Duration: {{ $interval->format(CarbonInterval::seconds($aggregatedData['seconds'])) }}</span><br>
    <span>Total cost: {{ Money::of(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->__toString(), $currency)->formatTo('en_US') }}</span><br>
</div>

<div id="main-chart" style="width: 100%; height:400px; margin-bottom: 50px;"></div>

@foreach($aggregatedData['grouped_data'] as $group1Entry)
    <h2>{{ $group1Entry['description'] ?? $group1Entry['key'] ?? 'No '.Str::lower($group->description()) }}</h2>

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
                Cost
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
                <td style="text-align: left;">
                    {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                </td>
                <td style="text-align: right;">
                    {{ $interval->format($duration) }}
                </td>
                <td style="text-align: right;">
                    {{ round($duration->totalHours, 2) }}
                </td>
                <td style="text-align: right;">
                    {{ Money::of(BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->__toString(), $currency)->formatTo('en_US') }}
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
            axisLabel: {
                show: true,
                interval: 0,
                rotate: 90,
            }
        },
        grid: {
            left: 0,
            right: 0,
            bottom: 0,
            containLabel: true
        },
        yAxis: {
            minInterval: 1,
            axisLabel: {
                show: false,
                inside: true,
                formatter: function (value, index) {
                    let totalSeconds = value;
                    let hours = Math.floor(totalSeconds / 3600);
                    if (hours < 10) {
                        hours = '0' + hours;
                    }
                    totalSeconds %= 3600;
                    let minutes = Math.floor(totalSeconds / 60);
                    if (minutes < 10) {
                        minutes = '0' + minutes;
                    }
                    let seconds = totalSeconds % 60;
                    if (seconds < 10) {
                        seconds = '0' + seconds;
                    }
                    return hours + ':' + minutes + ':' + seconds;
                }
            }
        },
        series: [
            {
                name: 'time',
                type: 'bar',
                data: [{!! collect($dataHistoryChart['grouped_data'])->pluck('seconds')->implode(', ') !!}],
                label: {
                    show: true,
                    position: 'top',
                    formatter: function (params) {
                        let value = params.value;
                        if (value === 0) {
                            return '';
                        }
                        let totalSeconds = value;
                        let hours = Math.floor(totalSeconds / 3600);
                        if (hours < 10) {
                            hours = '0' + hours;
                        }
                        totalSeconds %= 3600;
                        let minutes = Math.floor(totalSeconds / 60);
                        if (minutes < 10) {
                            minutes = '0' + minutes;
                        }
                        let seconds = totalSeconds % 60;
                        if (seconds < 10) {
                            seconds = '0' + seconds;
                        }
                        return hours + ':' + minutes + ':' + seconds;
                    }
                }
            }
        ]
    };

    myChart.on('finished', () => {
        window.status = 'ready';
    })

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
</script>
</body>
</html>
