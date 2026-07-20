<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Offer Letter</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        p { margin: 3px 0; }
        .doc-header-table { width: 100%; }
        .doc-header-left { vertical-align: top; }
        .doc-header-right { vertical-align: top; width: 130px; text-align: center; }
        .doc-qr { width: 110px; height: 110px; }
        .doc-id { font-weight: 700; }
        .doc-verify-note { font-size: 10px; color: #6b7280; }
        .doc-verify-info { margin-top: 10px; font-size: 10px; color: #4b5563; }
        .doc-divider { border: none; border-top: 1px solid #d1d5db; margin: 14px 0; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.details th, table.details td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        table.details th { width: 32%; background: #f3f4f6; font-weight: 700; }
        .note { margin-top: 16px; font-size: 11px; color: #4b5563; }
    </style>
</head>
<body>
    @include('exports.partials.document-header', ['title' => 'Offer Letter'])

    <table class="details">
        <tr>
            <th>Application ID</th>
            <td>{{ $application->application_id }}</td>
        </tr>
        <tr>
            <th>Job</th>
            <td>{{ $application->job?->title ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ strtoupper((string) $application->status) }}</td>
        </tr>
    </table>

    <div class="note">
        <p>Dear {{ $applicant->user->name ?? 'Candidate' }},</p>
        <p>We are pleased to extend this provisional offer for the position listed above. Final terms will be confirmed by your recruiter.</p>
        <p>This document is system-generated and does not require signature.</p>
    </div>
</body>
</html>
