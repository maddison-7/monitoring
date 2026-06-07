<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Interview;

class CandidateCommunicationService
{
    /**
     * @return array{type:string,title:string,message:string}
     */
    public function applicationReceived(Application $application): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');

        return [
            'type' => 'application_received',
            'title' => 'Application received',
            'message' => 'We have received your application ' . (string) $application->application_id . ' for ' . $jobTitle . '. AI screening is now processing your profile, then HR will review your application.',
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function underReview(Application $application): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');

        return [
            'type' => 'application_under_review',
            'title' => 'Application under review',
            'message' => 'Your application ' . (string) $application->application_id . ' for ' . $jobTitle . ' is now under HR review. AI recommendations are suggestions only, and final decisions are made by HR.',
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function shortlisted(Application $application): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');

        return [
            'type' => 'application_shortlisted',
            'title' => 'Application shortlisted',
            'message' => 'Congratulations. You have been shortlisted for ' . $jobTitle . '. Please monitor your portal for interview steps.',
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function interviewScheduled(Application $application, Interview $interview): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');
        $interviewDateTime = optional($interview->scheduled_at)->format('Y-m-d H:i') ?? 'N/A';

        $details = [
            'Job: ' . $jobTitle,
            'Date & Time: ' . $interviewDateTime,
            'Mode: ' . strtoupper((string) $interview->mode),
            'Venue: ' . ((string) ($interview->venue ?? '') !== '' ? (string) $interview->venue : 'N/A'),
            'Meeting Link: ' . ((string) ($interview->meeting_link ?? '') !== '' ? (string) $interview->meeting_link : 'N/A'),
        ];

        return [
            'type' => 'interview_scheduled',
            'title' => 'Interview scheduled',
            'message' => 'Your interview has been scheduled.' . "\n\n" . implode("\n", $details),
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function offerSent(Application $application): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');
        $offerMessage = trim((string) ($application->offer_message ?? ''));
        if ($offerMessage === '') {
            $offerMessage = 'We are pleased to offer you a position for ' . $jobTitle . '. Please accept or reject the offer from your applicant portal.';
        }

        return [
            'type' => 'offer_sent',
            'title' => 'Job offer sent',
            'message' => $offerMessage,
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function rejectedByHr(Application $application, ?string $customMessage = null): array
    {
        $jobTitle = (string) ($application->job?->title ?? 'the selected role');
        $message = trim((string) $customMessage);
        if ($message === '') {
            $message = 'After HR review, your application for ' . $jobTitle . ' was not selected at this time. Thank you for your interest.';
        }

        return [
            'type' => 'application_rejected_by_hr',
            'title' => 'Application update',
            'message' => $message,
        ];
    }

    /**
     * @return array{type:string,title:string,message:string}
     */
    public function fromStatus(Application $application, string $status, ?string $customMessage = null): array
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'review', 'reviewed', 'under_review' => $this->underReview($application),
            'shortlisted' => $this->shortlisted($application),
            'offer_sent' => $this->offerSent($application),
            'rejected' => $this->rejectedByHr($application, $customMessage),
            default => [
                'type' => 'application_status_updated',
                'title' => 'Application status updated',
                'message' => trim((string) $customMessage) !== ''
                    ? (string) $customMessage
                    : ('Your application status is now: ' . strtoupper($normalized)),
            ],
        };
    }
}