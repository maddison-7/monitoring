<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function shortlistTopCandidates(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) ($validated['limit'] ?? $job->positions ?? 5);

        $applications = Application::query()
            ->where('job_id', $job->id)
            ->whereHas('aiScore')
            ->with(['aiScore', 'applicant.user'])
            ->orderByDesc(
                \App\Models\AiScore::query()
                    ->select('match_percentage')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->limit($limit)
            ->get();

        foreach ($applications as $application) {
            /** @var \App\Models\Application $application */
            $application->update(['status' => 'shortlisted']);
            $this->notificationService->notify(
                (int) $application->applicant->user_id,
                'shortlisted',
                'You have been shortlisted',
                'Congratulations. You were shortlisted for ' . $job->title . '.',
                $application->id
            );
        }

        $this->auditLogService->log(
            $request->user()->id,
            'shortlist.bulk_generated',
            Job::class,
            $job->id,
            null,
            ['count' => $applications->count()],
            $request
        );

        return response()->json([
            'message' => 'Top candidates shortlisted successfully.',
            'count' => $applications->count(),
        ]);
    }
}
