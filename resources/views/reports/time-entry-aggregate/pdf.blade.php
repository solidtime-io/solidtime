@use('Brick\Math\BigDecimal')
@use('Brick\Money\Money')
@use('Illuminate\Support\Carbon')
@use('Carbon\CarbonInterval')
@inject('colorService', 'App\Service\ColorService')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Report</title>
    <style>
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed,
        figure, figcaption, footer, header, hgroup,
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            vertical-align: baseline;
            box-sizing: border-box;
        }


        /* HTML5 display-role reset for older browsers */
        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }

        body {
            line-height: 1;
        }

        ol, ul {
            list-style: none;
        }

        blockquote, q {
            quotes: none;
        }

        blockquote:before, blockquote:after,
        q:before, q:after {
            content: '';
            content: none;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            text-align: left;
        }

        @font-face {
            font-family: 'Outfit';
            src: url('outfit.ttf');
        }

        body {
            font-family: 'Outfit', 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #18181b
        }

        table {
            font-size: 14px;
        }

        thead {
            border-bottom: 1px #d4d4d8 solid;
        }

        tfoot {
            border-top: 1px #d4d4d8 solid;
        }

        table th, table tfoot td {
            font-weight: 500;
            padding: 6px 12px;
            color: #18181b;
        }

        .table-wrapper table th {
            background-color: #fafafa;
        }

        .table-wrapper {
            border: 1px solid #d4d4d8;
            border-radius: 8px;
            overflow: hidden;
            width: calc(100% - 2px)
        }

        table tr {
            border-bottom: 1px #e4e4e7 solid;
        }

        table tr:last-of-type {
            border-bottom: none;
        }

        table tr td {
            font-weight: 400;
            color: #3f3f46;
            padding: 6px 12px;
        }

        .data-table {
            break-after: auto;
        }

        .no-break {
            break-after: avoid-page;
        }
    </style>
    <script>
        window.status = "processing";
    </script>
    <script
        src="{{ $debug ? 'https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js' : 'echarts.min.js' }}"></script>

    @if($debug)
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:200,300,400,500,600,700,800" rel="stylesheet"/>
    @endif

</head>
<body>
<div>
    <p style="font-size: 32px; font-weight: 600; margin-bottom: 5px;">Report</p>
    <div style="font-size: 16px; font-weight: 600; color: #71717a;">
        <span>{{ $localization->formatDate($start->timezone($timezone)) }} - {{ $localization->formatDate($end->timezone($timezone)) }}</span><br><br>
    </div>

</div>


<div class="table-wrapper">
    <div
        style="background-color: #fafafa; padding: 5px 14px; border-bottom: 1px #d4d4d8 solid; display: flex; gap: 20px;">
        <div style="padding: 8px 12px; border-radius: 8px;">
            <div style="color: #71717a; font-weight: 600;">Duration</div>
            <div
                style="font-size: 24px; font-weight: 500; margin-top: 2px;">{{ $localization->formatInterval(CarbonInterval::seconds($aggregatedData['seconds'])) }} </div>
        </div>
        @if($showBillableRate)
        <div style="padding: 8px 12px; border-radius: 8px;">
            <div style="color: #71717a; font-weight: 600;">Total cost</div>
            <div
                style="font-size: 24px; font-weight: 500; margin-top: 2px;">{{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->__toString(), $currency)) }} </div>
        </div>
        @endif
    </div>
    <div id="main-chart" style="width: 700px; height: 300px; margin: 20px auto;"></div>

</div>


<div style="display: flex; align-items: center; padding-top: 40px;">
    <div style="padding: 10px 0;">
        <div id="pie-chart" style="width: 300px; height: 180px; margin-bottom: 20px;"></div>
    </div>
    <div style="flex: 1 1 0%;">
        <div class="">
            <table style="width: 100%; ">
                <thead>
                <tr>
                    <th>
                        {{ $group->description() }}
                    </th>
                    <th>Duration</th>
                    @if($showBillableRate)
                    <th style="text-align: right;">Cost</th>
                    @endif
                </tr>
                </thead>
                @foreach($aggregatedData['grouped_data'] as $group1Entry)
                    <tr>
                        <td style="display: flex; align-items: center;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background-color: {{
                        $group1Entry['color'] ?? ($group1Entry['key'] ? $colorService->getRandomColor($group1Entry['key']) : '#CCCCCC')
 }};">
                            </div>
                            <span style="padding-left: 8px;">
                                @if($group->is(\App\Enums\TimeEntryAggregationType::Billable))
                                    {{ $group1Entry['key'] === '1' ? 'Billable' : 'Non-billable' }}
                                @else
                                    {{ $group1Entry['description'] ?? $group1Entry['key'] ?? 'No '.Str::lower($group->description()) }}
                                @endif
                            </span>
                        </td>
                        <td style="text-align: left;">
                            {{ $localization->formatInterval(CarbonInterval::seconds($group1Entry['seconds'])) }}
                        </td>
                        @if($showBillableRate)
                        <td style="text-align: right;">
                            {{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($group1Entry['cost'], 2)->__toString(), $currency)) }}
                        </td>
                        @endif
                    </tr>
                @endforeach
                <tfoot>
                <tr>
                    <td style="font-weight: 500;color: #18181b;">
                        Total
                    </td>
                    <td style="font-weight: 500;color: #18181b;">
                        {{ $localization->formatInterval(CarbonInterval::seconds($aggregatedData['seconds'])) }}
                    </td>
                    @if($showBillableRate)
                    <td style="text-align: right; font-weight: 500;color: #18181b;">
                        {{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->__toString(), $currency)) }}
                    </td>
                    @endif
                </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>

@foreach($aggregatedData['grouped_data'] as $group1Entry)
    <div class="data-table">
        <h2 class="no-break"
            style="padding-top: 16px; padding-bottom: 8px; font-size: 16px; font-weight: 600; padding-left: 6px; color: #3f3f46;">
            @if($group->is(\App\Enums\TimeEntryAggregationType::Billable))
                {{ $group1Entry['key'] === '1' ? 'Billable' : 'Non-billable' }}
            @else
                <span style="color: #a1a1aa;">
                    {{ $group->description() }}:
                    </span>
                {{ $group1Entry['description'] ?? $group1Entry['key'] ?? 'No '.Str::lower($group->description()) }}
            @endif
        </h2>

        <div class="table-wrapper">
            <table style="width: 100%;">
                <thead>
                <tr>
                    <th>
                        {{ $subGroup->description() }}
                    </th>
                    <th>
                        Duration
                    </th>
                    <th>
                        Duration (h)
                    </th>
                    @if($showBillableRate)
                    <th>
                        Cost
                    </th>
                    @endif
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
                            @if($subGroup->is(\App\Enums\TimeEntryAggregationType::Billable))
                                {{ $group2Entry['key'] === '1' ? 'Billable' : 'Non-billable' }}
                            @else
                                {{ $group2Entry['description'] ?? $group2Entry['key'] ?? '-' }}
                            @endif
                        </td>
                        <td>
                            {{ $localization->formatInterval($duration) }}
                        </td>
                        <td>
                            {{ $localization->formatNumber($duration->totalHours) }}
                        </td>
                        @if($showBillableRate)
                        <td>
                            {{ $localization->formatCurrency(Money::of(BigDecimal::ofUnscaledValue($group2Entry['cost'], 2)->__toString(), $currency)) }}
                        </td>
                        @endif
                    </tr>
                    @php
                        $totalDuration += $group2Entry['seconds'];
                        if ($showBillableRate) {
                            $totalCost += $group2Entry['cost'];
                        }
                    @endphp
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endforeach

<script>
    let elementPieChart = document.getElementById("pie-chart");
    let pieChart = echarts.init(elementPieChart, null, {
        renderer: "svg"
    });
    let pieChartOptions = {
        animation: false,
        backgroundColor: "transparent",

        series: [
            {
                data: {!! json_encode(collect($aggregatedData['grouped_data'])->map(function (array $data) use (&$colorService, $group): object {
                    $color = $data['color'];
                    if ($color === null) {
                        $color = $colorService->getRandomColor($data['key']);
                    }
                    if ($data['key'] === null) {
                       $color = '#CCCCCC';
                    }
                    return (object)[
                        'value' => $data['seconds'],
                        'name' => $data['description'] ?? $data['key'] ?? 'No '.Str::lower($group->description()),
                        'color' => $color,
                        'itemStyle' => (object) [
                            'color' => $color,
                        ],
                        'emphasis' => (object) [
                            'itemStyle' => (object) [
                                'color' => $color,
                            ],
                        ],
                    ];
                })->toArray()) !!},
                radius: ["40%", "80%"],
                type: "pie",
                label: {
                    formatter: "{d}%",
                    overflow: "truncate"
                }
            }
        ]
    };
    pieChart.on("finished", () => {
        window.pieChartFinished = true;
        if (window.mainChartFinished && window.pieChartFinished) {
            window.status = "ready";
        }
    });
    pieChart.setOption(pieChartOptions);

    let elementMainChart = document.getElementById("main-chart");
    let mainChart = echarts.init(elementMainChart, null, {
        renderer: "svg"
    });
    let mainChartOptions = {
        animation: false,
        tooltip: {},
        xAxis: {
            data: {!!
                json_encode(collect($dataHistoryChart['grouped_data'])
                    ->pluck('key')
                    ->map(fn($value) => $localization->formatDate(Carbon::parse($value)))
                    ->toArray())
            !!},
            axisLabel: {
                fontSize: 10,
                fontWeight: 400,
                color: "rgb(120, 120, 120)",
                margin: 16,
                fontFamily: "Outfit, sans-serif"
            },
            axisTick: {
                interval: 0,
                alignWithLabel: true
            }
        },
        grid: {
            containLabel: true,
            left: 15,
            top: 15,
            right: 15,
            bottom: 0
        },
        yAxis: {
            minInterval: 1,
            axisLabel: {
                show: false,
                inside: true,
            }
        },
        series: [
            {
                name: "time",
                type: "bar",
                data: {!! json_encode(collect($dataHistoryChart['grouped_data'])->map(fn($value) => (object) [
                            'value' => $value['seconds'],
                            'name' => ((int) $value['seconds']) === 0 ? '' : $localization->formatInterval(CarbonInterval::seconds((int) $value['seconds']))
                        ])->toArray()) !!},
                itemStyle: {
                    borderColor: "#7dd3fc",
                    color: "#7dd3fc"
                },
                label: {
                    show: true,
                    @if(count($dataHistoryChart['grouped_data']) > 15)
                    rotate: 90,
                    offset: [10, 5],
                    @endif
                    fontSize: 10,
                    position: "top",
                    formatter: function (params) {
                        return params.name;
                    }
                }
            }
        ]
    };
    mainChart.on("finished", () => {
        window.mainChartFinished = true;
        if (window.mainChartFinished && window.pieChartFinished) {
            window.status = "ready";
        }
    });
    mainChart.setOption(mainChartOptions);
</script>
</body>
</html>
