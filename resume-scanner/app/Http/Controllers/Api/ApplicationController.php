<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiScore;
use App\Models\Application;
use App\Models\Job;
use App\Services\Ai\RecruitmentScoringService;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly RecruitmentScoringService $recruitmentScoringService,
        private readonly NotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function apply(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'cover_letter_text' => ['nullable', 'string'],
        ]);

        if ($job->status !== 'published') {
            return response()->json(['message' => 'This vacancy is not open for applications.'], 422);
        }

        if ($job->application_deadline && now()->gt($job->application_deadline)) {
            return response()->json(['message' => 'Application deadline has passed.'], 422);
        }

        $applicant = $request->user()->applicant()->with(['skills', 'educations', 'experiences', 'certificates'])->firstOrFail();

        if (empty($applicant->cv_path)) {
            return response()->json(['message' => 'Please upload a CV before applying.'], 422);
        }

        $isIncomplete = $applicant->skills()->count() === 0 || $applicant->educations()->count() === 0;
        $missingQualification = $this->missingQualification($applicant, $job);

        $duplicate = Application::query()
            ->where('job_id', $job->id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if ($duplicate) {
            return response()->json(['message' => 'Duplicate application is not allowed.'], 409);
        }

        $applicationId = $this->generateApplicationId();
        $duplicateHash = hash('sha256', implode('|', [
            $job->id,
            $applicant->id,
            (string) ($applicant->cv_hash ?? ''),
        ]));

        $application = Application::query()->create([
            'application_id' => $applicationId,
            'job_id' => $job->id,
            'applicant_id' => $applicant->id,
            'cover_letter_text' => $validated['cover_letter_text'] ?? null,
            'status' => 'submitted',
            'duplicate_hash' => $duplicateHash,
            'applied_at' => now(),
        ]);

        $scorePayload = $this->recruitmentScoringService->score($applicant, $job);

        if ($isIncomplete || $missingQualification) {
            $scorePayload['match_percentage'] = min((float) $scorePayload['match_percentage'], 20.0);
            $scorePayload['recommendation_level'] = 'Rejected';
            $scorePayload['summary'] = $isIncomplete
                ? 'Auto-rejected: incomplete application profile.'
                : 'Auto-rejected: missing required qualifications.';
        }

        $applicationStatus = ($isIncomplete || $missingQualification)
            ? 'auto_rejected'
            : $this->mapRecommendationToStatus((string) $scorePayload['recommendation_level']);

        $application->update(['status' => $applicationStatus]);

        AiScore::query()->create([
            'application_id' => $application->id,
            'match_percentage' => $scorePayload['match_percentage'],
            'recommendation_level' => $scorePayload['recommendation_level'],
            'matched_skills' => $scorePayload['matched_skills'],
            'missing_skills' => $scorePayload['missing_skills'],
            'summary' => $scorePayload['summary'],
            'model_name' => (string) config('services.gemini.model', 'local-ai-scoring'),
        ]);

        $this->notificationService->notify(
            $request->user()->id,
            'application_submitted',
            $applicationStatus === 'auto_rejected' ? 'Application auto-rejected' : 'Application received',
            $applicationStatus === 'auto_rejected'
                ? 'Your application ' . $applicationId . ' was auto-rejected by screening rules.'
                : 'Your application ' . $applicationId . ' is now in ' . strtoupper($applicationStatus) . ' status.',
            $application->id
        );

        $this->notificationService->notify(
            $job->hr_officer_id,
            'new_application',
            'New application received',
            'A new application was submitted for ' . $job->title . '.',
            $application->id
        );

        $this->auditLogService->log(
            $request->user()->id,
            'application.created',
            Application::class,
            $application->id,
            null,
            $application->toArray(),
            $request
        );

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application->load('aiScore'),
        ], 201);
    }

    public function myApplications(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant;

        $applications = Application::query()
            ->where('applicant_id', $applicant->id)
            ->with(['job:id,title,application_deadline', 'aiScore'])
            ->latest('applied_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($applications);
    }

    public function listForHr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'status' => ['nullable', 'string'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'skill' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string'],
        ]);

        $applications = Application::query()
            ->where('job_id', (int) $validated['job_id'])
            ->with(['applicant.user:id,name,email', 'applicant.educations', 'applicant.skills', 'aiScore'])
            ->when(!empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['min_score']), function ($query) use ($validated): void {
                $query->whereHas('aiScore', fn ($scoreQuery) => $scoreQuery->where('match_percentage', '>=', (float) $validated['min_score']));
            })
            ->when(!empty($validated['skill']), function ($query) use ($validated): void {
                $needle = strtolower((string) $validated['skill']);
                $query->whereHas('applicant.skills', fn ($skillQuery) => $skillQuery->whereRaw('LOWER(name) like ?', ['%' . $needle . '%']));
            })
            ->when(!empty($validated['education_level']), function ($query) use ($validated): void {
                $needle = strtolower((string) $validated['education_level']);
                $query->whereHas('applicant.educations', fn ($eduQuery) => $eduQuery->whereRaw('LOWER(level) like ?', ['%' . $needle . '%']));
            })
            ->orderByDesc(
                AiScore::query()
                    ->select('match_percentage')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($applications);
    }

    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:review,under_review,shortlisted,interview_scheduled,hired,rejected,auto_rejected'],
            'message' => ['nullable', 'string'],
        ]);

        $before = $application->toArray();
        $application->update(['status' => $validated['status']]);

        $applicantUserId = (int) $application->applicant->user_id;
        $this->notificationService->notify(
            $applicantUserId,
            'application_status_updated',
            'Application status updated',
            $validated['message'] ?? ('Your application status is now: ' . $validated['status']),
            $application->id
        );

        $this->auditLogService->log(
            $request->user()->id,
            'application.status_updated',
            Application::class,
            $application->id,
            $before,
            $application->fresh()->toArray(),
            $request
        );

        return response()->json([
            'message' => 'Application status updated.',
            'application' => $application->fresh(),
        ]);
    }

    private function generateApplicationId(): string
    {
        return 'APP-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    private function missingQualification(\App\Models\Applicant $applicant, Job $job): bool
    {
        $qualification = strtolower((string) $job->qualifications);
        if (trim($qualification) === '') {
            return false;
        }

        if (str_contains($qualification, 'bachelor')) {
            return !$applicant->educations()->whereRaw('LOWER(level) like ?', ['%bachelor%'])->exists();
        }

        if (str_contains($qualification, 'master')) {
            return !$applicant->educations()->whereRaw('LOWER(level) like ?', ['%master%'])->exists();
        }

        return false;
    }

    private function mapRecommendationToStatus(string $recommendation): string
    {
        return match (strtolower(trim($recommendation))) {
            'shortlisted', 'highly qualified' => 'shortlisted',
            'review', 'moderately qualified' => 'review',
            'rejected', 'unqualified' => 'rejected',
            default => 'review',
        };
    }
}
