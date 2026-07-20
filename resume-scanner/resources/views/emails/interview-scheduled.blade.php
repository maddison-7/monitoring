<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Interview Invitation</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family: Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:32px;">
                            <h1 style="margin:0 0 8px; font-size:20px;">Interview Invitation</h1>
                            <p style="margin:0 0 16px; font-size:14px; color:#475569;">Dear {{ $applicantName }},</p>
                            <p style="margin:0 0 16px; font-size:14px; color:#475569;">
                                You have been invited to an interview for the <strong>{{ $jobTitle }}</strong> position. Details are below.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0; border-collapse:collapse;">
                                <tr>
                                    <td style="padding:8px 0; font-size:13px; color:#64748b; width:40%;">Date &amp; Time</td>
                                    <td style="padding:8px 0; font-size:13px; font-weight:600;">{{ $scheduledAt }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; font-size:13px; color:#64748b;">Mode</td>
                                    <td style="padding:8px 0; font-size:13px; font-weight:600;">{{ $mode }}</td>
                                </tr>
                                @if($venue !== '')
                                <tr>
                                    <td style="padding:8px 0; font-size:13px; color:#64748b;">Venue</td>
                                    <td style="padding:8px 0; font-size:13px; font-weight:600;">{{ $venue }}</td>
                                </tr>
                                @endif
                                @if($meetingLink !== '')
                                <tr>
                                    <td style="padding:8px 0; font-size:13px; color:#64748b;">Meeting Link</td>
                                    <td style="padding:8px 0; font-size:13px; font-weight:600;"><a href="{{ $meetingLink }}">{{ $meetingLink }}</a></td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:8px 0; font-size:13px; color:#64748b;">Recruiter Contact</td>
                                    <td style="padding:8px 0; font-size:13px; font-weight:600;">{{ $recruiterName }}{{ $recruiterEmail !== '' ? ' (' . $recruiterEmail . ')' : '' }}</td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0; font-size:13px; color:#475569;">
                                <strong>Instructions:</strong> Please be available at least 10 minutes before the scheduled time and bring a valid form of identification. You can also view this invitation and respond from your applicant portal at any time.
                            </p>

                            <p style="margin:24px 0 0; font-size:12px; color:#94a3b8;">
                                This is an automated message from the recruitment system.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
