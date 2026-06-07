<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Admin Export' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            margin: 24px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 6px;
        }

        .meta {
            color: #6b7280;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Admin Export' }}</h1>
    <div class="meta">Generated: {{ $generatedAt ?? now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                @foreach (($columns ?? []) as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse (($rows ?? []) as $row)
                <tr>
                    @foreach (($columns ?? []) as $column)
                        <td>{{ (string) ($row[$column] ?? '') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($columns ?? [])) }}">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
