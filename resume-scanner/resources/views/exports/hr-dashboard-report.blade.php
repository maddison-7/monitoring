<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>HR Dashboard Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>HR Dashboard Report</h1>
    <p>Generated at: {{ now()->format('Y-m-d H:i') }}</p>

    <h2>Core Metrics</h2>
    <table>
        <thead><tr><th>Metric</th><th>Value</th></tr></thead>
        <tbody>
            @foreach(($data['metrics'] ?? []) as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top AI Candidates</h2>
    <table>
        <thead><tr><th>Name</th><th>Score</th></tr></thead>
        <tbody>
            @forelse(($data['topCandidates'] ?? []) as $item)
                <tr><td>{{ $item['name'] }}</td><td>{{ number_format((float)$item['score'], 1) }}%</td></tr>
            @empty
                <tr><td colspan="2">No top candidates.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Department Performance</h2>
    <table>
        <thead><tr><th>Department</th><th>Applicants</th></tr></thead>
        <tbody>
            @forelse(($data['departmentStats'] ?? []) as $item)
                <tr><td>{{ $item['department'] }}</td><td>{{ $item['count'] }}</td></tr>
            @empty
                <tr><td colspan="2">No department data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
