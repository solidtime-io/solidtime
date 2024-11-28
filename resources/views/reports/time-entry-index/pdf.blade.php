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

        .data {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <h1>Detailed Report</h1>

    <hr>

    <div class="range">
        <span>{{ $start->format('d.m.Y') }} - {{ $end->format('d.m.Y') }}</span><br><br>
    </div>

    <div class="properties">
        <span>Duration: {{ $interval->format(CarbonInterval::seconds($aggregatedData['seconds'])) }}</span><br>
        <span>Total cost: {{ Money::of(BigDecimal::ofUnscaledValue($aggregatedData['cost'], 2)->__toString(), $currency)->formatTo('en_US') }}</span><br>
    </div>

    <div class="data">
        <table>
            <thead>
            <tr>
                <th>Description</th>
                <th>Task</th>
                <th>Project</th>
                <th>Client</th>
                <th>User</th>
                <th>Duration</th>
                <th>Billable</th>
                <th>Tags</th>
            </tr>
            </thead>
            <tbody>
            @foreach($timeEntries as $timeEntry)
                <tr>
                    <td>{{ $timeEntry->description === '' ? '-' : $timeEntry->description }}</td>
                    <td>{{ $timeEntry->task?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->project?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->client?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->user->name }}</td>
                    <td>
                        {{ $timeEntry->start->format('Y-m-d H:i:s') }} - {{ $timeEntry->end->format('Y-m-d H:i:s') }}
                    </td>
                    <td>{{ $timeEntry->billable ? 'Yes' : 'No' }}</td>
                    <td>{{ count($timeEntry->tagsRelation) === 0 ? '-' : $timeEntry->tagsRelation->implode('name', ', ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
