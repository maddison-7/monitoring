<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function schedule(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', 'string', 'in:online,physical'],
            'meeting_link' => ['nullable', 'url'],
            'venue' => ['nullable', 'string', 'max:255'],
        ]);

        $interview = Interview::query()->create([
            'application_id' => $application->id,
            'interviewer_id' => $request->user()->id,
            'scheduled_at' => $validated['scheduled_at'],
            'mode' => $validated['mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => 'scheduled',
        ]);

        $application->update(['status' => 'interview_scheduled']);

        $this->notificationService->notify(
            (int) $application->applicant->user_id,
            'interview_scheduled',
            'Interview scheduled',
            'Your interview is scheduled on ' . $interview->scheduled_at->format('Y-m-d H:i') . '.',
            $interview->id
        );

        $this->auditLogService->log(
            $request->user()->id,
            'interview.scheduled',
            Interview::class,
            $interview->id,
            null,
            $interview->toArray(),
            $request
        );

        return response()->json([
            'message' => 'Interview scheduled successfully.',
            'interview' => $interview,
        ], 201);
    }

    public function updateResult(Request $request, Interview $interview): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,cancelled,no_show'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result_notes' => ['nullable', 'string'],
            'final_decision' => ['nullable', 'string', 'in:hired,rejected'],
        ]);

        $before = $interview->toArray();

        $interview->update([
            'status' => $validated['status'],
            'interview_score' => $validated['interview_score'] ?? null,
            'result_notes' => $validated['result_notes'] ?? null,
        ]);

        if (!empty($validated['final_decision'])) {
            $interview->application->update(['status' => $validated['final_decision']]);
            $title = $validated['final_decision'] === 'hired' ? 'Application accepted' : 'Application update';
            $message = $validated['final_decision'] === 'hired'
                ? 'Congratulations, you have been selected.'
                : 'Thank you for applying. Your application was not successful this time.';

            $this->notificationService->notify(
                (int) $interview->application->applicant->user_id,
                'final_recruitment_result',
                $title,
                $message,
                $interview->application_id
            );
        }

        $this->auditLogService->log(
            $request->user()->id,
            'interview.updated',
            Interview::class,
            $interview->id,
            $before,
            $interview->fresh()->toArray(),
            $request
        );

        return response()->json([
            'message' => 'Interview result updated.',
            'interview' => $interview->fresh(),
        ]);
    }
}
