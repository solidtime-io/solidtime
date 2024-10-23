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
</head>
<body>
    <h1>Report</h1>

    <div>
        <span>01.01.2020 - 01.01.2024</span>
    </div>

    <div>
        <span>Duration: 20:10:10</span>
    </div>

    <div>
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
                    <td>{{ $timeEntry->description }}</td>
                    <td>{{ $timeEntry->task?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->project?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->client?->name ?? '-' }}</td>
                    <td>{{ $timeEntry->user->name }}</td>
                    <td>
                        00:00:01
                        {{ $timeEntry->start->format('Y-m-d H:i:s') }} - {{ $timeEntry->end->format('Y-m-d H:i:s') }}
                    </td>
                    <td>{{ $timeEntry->billable ? 'Yes' : 'no' }}</td>
                    <td>{{ $timeEntry->tagsRelation->implode('name', ', ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
