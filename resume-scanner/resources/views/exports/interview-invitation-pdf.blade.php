<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Interview Invitation</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        .heading { margin-bottom: 10px; }
        h1 { margin: 0; font-size: 20px; }
        p { margin: 4px 0; }
        .meta { margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        th { width: 32%; background: #f3f4f6; font-weight: 700; }
        .note { margin-top: 16px; font-size: 11px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="heading">
        <h1>Interview Invitation</h1>
        <p>Reference: INTV-{{ $data['interviewId'] }}</p>
        <p>Generated At: {{ $data['generatedAt'] }}</p>
    </div>

    <table>
        <tr>
            <th>Job Position</th>
            <td>{{ $data['jobTitle'] }}</td>
        </tr>
        <tr>
            <th>Scheduled Date and Time</th>
            <td>{{ $data['scheduledAt'] }}</td>
        </tr>
        <tr>
            <th>Interview Mode</th>
            <td>{{ $data['mode'] }}</td>
        </tr>
        <tr>
            <th>Venue</th>
            <td>{{ $data['venue'] }}</td>
        </tr>
        <tr>
            <th>Meeting Link</th>
            <td>{{ $data['meetingLink'] }}</td>
        </tr>
    </table>

    <div class="meta">
        <p>Dear Candidate,</p>
        <p>You are invited to attend the interview session for the role listed above. Please be available at least 10 minutes before the scheduled time.</p>
        <p class="note">This invitation is system-generated and does not require signature.</p>
    </div>
</body>
</html>
