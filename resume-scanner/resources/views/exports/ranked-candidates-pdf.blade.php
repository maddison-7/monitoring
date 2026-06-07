@php
    $columns = ['Rank', 'Candidate', 'Skills', 'Experience (years)', 'Score', 'Recommendation'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ranked Candidates - PDF Export</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #888; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        h2 { margin-bottom: 0; }
        .subtitle { color: #666; font-size: 13px; margin-top: 0; }
    </style>
</head>
<body>
    <h2>Ranked Candidates</h2>
    <div class="subtitle">Job: <strong>{{ $job->title }}</strong>@if($job->department) ({{ $job->department }})@endif</div>
    <table>
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($candidates as $index => $candidate)
                @php
                    $candidateName = trim((string) ($candidate->parsed_json['full_name'] ?? ''));
                    if ($candidateName === '' || strtolower($candidateName) === 'candidate') {
                        $candidateName = pathinfo((string) ($candidate->resume_original_name ?? ('Candidate ' . ($index + 1))), PATHINFO_FILENAME);
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $candidateName }}</td>
                    <td>{{ collect($candidate->skills_json ?? [])->take(4)->implode(', ') }}</td>
                    <td>{{ number_format((float)($candidate->years_experience ?? 0), 1) }}</td>
                    <td>{{ (int)($candidate->match_score ?? 0) }}%</td>
                    <td>{{ $candidate->recommendation ?? 'Review' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
