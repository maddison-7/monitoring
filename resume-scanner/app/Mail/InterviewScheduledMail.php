<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Interview $interview)
    {
    }

    public function envelope(): Envelope
    {
        $jobTitle = (string) ($this->interview->application?->job?->title ?? 'Recruitment Interview');

        return new Envelope(
            subject: 'Interview Invitation - ' . $jobTitle,
        );
    }

    public function content(): Content
    {
        $application = $this->interview->application;

        return new Content(
            view: 'emails.interview-scheduled',
            with: [
                'applicantName' => (string) ($application?->applicant?->user?->name ?? 'Candidate'),
                'jobTitle' => (string) ($application?->job?->title ?? 'the selected role'),
                'scheduledAt' => optional($this->interview->scheduled_at)->format('l, F j, Y \a\t g:i A') ?? 'N/A',
                'mode' => strtoupper((string) $this->interview->mode),
                'venue' => (string) ($this->interview->venue ?: ''),
                'meetingLink' => (string) ($this->interview->meeting_link ?: ''),
                'recruiterName' => (string) ($this->interview->interviewer?->name ?? 'Recruitment Team'),
                'recruiterEmail' => (string) ($this->interview->interviewer?->email ?? ''),
            ]
        );
    }
}
