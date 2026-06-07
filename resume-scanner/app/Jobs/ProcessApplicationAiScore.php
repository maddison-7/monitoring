<?php

namespace App\Jobs;

use App\Models\AiScore;
use App\Models\Application;
use App\Services\Ai\RecruitmentScoringService;
use App\Services\CandidateCommunicationService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessApplicationAiScore implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(private readonly int $applicationId)
    {
    }

    public function handle(
        RecruitmentScoringService $recruitmentScoringService,
        NotificationService $notificationService,
        CandidateCommunicationService $candidateCommunicationService
    ): void {
        $application = Application::query()
            ->with(['applicant.user', 'job', 'applicant.skills', 'applicant.educations', 'applicant.experiences', 'applicant.certificates'])
            ->find($this->applicationId);

        if (!$application instanceof Application || !$application->applicant || !$application->job) {
            return;
        }

        $score = $recruitmentScoringService->score($application->applicant, $application->job);

        AiScore::query()->updateOrCreate(
            ['application_id' => (int) $application->id],
            [
                'match_percentage' => $score['match_percentage'],
                'recommendation_level' => $score['recommendation_level'],
                'matched_skills' => $score['matched_skills'],
                'missing_skills' => $score['missing_skills'],
                'summary' => $score['summary'],
                'model_name' => (string) config('services.gemini.model', 'local-ai-scoring'),
            ]
        );

        $currentStatus = strtolower((string) $application->status);
        if (in_array($currentStatus, ['ai_processing', 'submitted', 'pending', 'under_review', 'reviewed'], true)) {
            $application->update(['status' => 'review']);
        }

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        if ($applicantUserId > 0) {
            $template = $candidateCommunicationService->underReview($application);
            $notificationService->notify(
                $applicantUserId,
                $template['type'],
                $template['title'],
                $template['message'],
                (int) $application->id,
                true,
                true
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        $application = Application::query()->with(['applicant.user', 'job'])->find($this->applicationId);
        if (!$application instanceof Application) {
            return;
        }

        if (strtolower((string) $application->status) === 'ai_processing') {
            $application->update(['status' => 'review']);
        }
    }
}