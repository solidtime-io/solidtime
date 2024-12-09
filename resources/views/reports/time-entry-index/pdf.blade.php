@use('Brick\Math\BigDecimal')
@use('Brick\Money\Money')
@use('PhpOffice\PhpSpreadsheet\Cell\DataType')
@use('Carbon\CarbonInterval')
@inject('interval', 'App\Service\IntervalService')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
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

        @font-face {
            font-family: 'Outfit';
            src: url('outfit.ttf');
        }

        body {
            font-family: 'Outfit', 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #18181b
        }

        table {
            font-size: 10px;
        }

        table thead {
            background-color: #eee;
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

        table {
            border-collapse: collapse;
            border-spacing: 0;
            text-align: left;
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

        table td, table th {
            font-size: 12px;
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

    </style>
</head>
<body>
<div>
    <p style="font-size: 32px; font-weight: 600; margin-bottom: 5px;">Detailed Report</p>
    <div style="font-size: 16px; font-weight: 600; color: #71717a;">
        <span>{{ $start->format('d.m.Y') }} - {{ $end->format('d.m.Y') }}</span><br><br>
    </div>
</div>
<div class="table-wrapper">
    <div
        style="background-color: #fafafa; padding: 5px 14px; display: flex; gap: 20px;">
        <div style="padding: 8px 12px; border-radius: 8px;">
            <div style="color: #71717a; font-weight: 600;">Duration</div>
            <div
                style="font-size: 24px; font-weight: 500; margin-top: 2px;">{{ $interval->format(CarbonInterval::seconds($aggregatedData['seconds'])) }} </div>
        </div>
        <div style="padding: 8px 12px; border-radius: 8px;">
            <div style="color: #71717a; font-weight: 600;">Total cost</div>
            <div
                style="font-size: 24px; font-weight: 500; margin-top: 2px;">{{ Money::of(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->__toString(), $currency)->formatTo('en_US') }} </div>
        </div>

    </div>
    <div>
        <table style="width: 100%;">
            <thead>
            <tr style="border-top: 1px #d4d4d8 solid;">
                <th>Time Entry</th>
                <th>User</th>
                <th style="text-align: center;">Time</th>
                <th>Duration</th>
                <th>Billable</th>
                <th>Tags</th>
            </tr>
            </thead>
            <tbody>
            @foreach($timeEntries as $timeEntry)
                <tr>
                    <td style="overflow-wrap: break-word; max-width: 250px;">
                        {{ $timeEntry->description === '' ? '-' : $timeEntry->description }} <br>
                        @if($timeEntry->task?->name)
                            <span style="font-weight: 600;">Task:</span> {{ $timeEntry->task?->name ?? '-' }} <br>
                        @endif
                        @if($timeEntry->project?->name)
                            <span style="font-weight: 600;">Project:</span> {{ $timeEntry->project?->name }} <br>
                        @endif
                        @if($timeEntry->client?->name)
                            <span style="font-weight: 600;">
                                    Client:
                                </span>{{ $timeEntry->client?->name }} <br>
                        @endif
                    </td>
                    <td style="overflow-wrap: break-word; min-width: 75px;">{{ $timeEntry->user->name }}</td>
                    <td style="overflow-wrap: break-word; min-width: 150px; text-align: center;">
                        @if($timeEntry->start->format('Y-m-d') === $timeEntry->end->format('Y-m-d'))
                            {{ $timeEntry->start->format('Y-m-d') }}
                        @else
                            {{ $timeEntry->start->format('Y-m-d') }} - <br> {{ $timeEntry->end->format('Y-m-d') }}
                        @endif
                        <br>
                        {{ $timeEntry->start->format('H:i:s') }} - {{ $timeEntry->end->format('H:i:s') }}
                    </td>
                    <td style="overflow-wrap: break-word; min-width: 75px;">
                        {{ $interval->format($timeEntry->getDuration()) }}
                    </td>
                    <td style="overflow-wrap: break-word;">{{ $timeEntry->billable ? 'Yes' : 'No' }}</td>
                    <td style="overflow-wrap: break-word; min-width: 75px;">{{ count($timeEntry->tagsRelation) === 0 ? '-' : $timeEntry->tagsRelation->implode('name', ', ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>


</body>
</html>
