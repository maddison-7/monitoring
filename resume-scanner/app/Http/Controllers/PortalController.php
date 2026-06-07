<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessApplicationAiScore;
use App\Models\AiScore;
use App\Models\Applicant;
use App\Models\ApplicantLanguage;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Education;
use App\Models\Interview;
use App\Models\Job;
use App\Models\LoginHistory;
use App\Models\Notification;
use App\Models\SavedJob;
use App\Models\Skill;
use App\Services\Ai\CvAnalysisService;
use App\Services\Ai\RecruitmentScoringService;
use App\Services\AuditLogService;
use App\Services\CandidateCommunicationService;
use App\Services\NotificationService;
use App\Services\OutboundChannelService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    public function __construct(
        private readonly RecruitmentScoringService $recruitmentScoringService,
        private readonly CvAnalysisService $cvAnalysisService,
        private readonly NotificationService $notificationService,
        private readonly CandidateCommunicationService $candidateCommunicationService,
        private readonly OutboundChannelService $outboundChannelService,
        private readonly AuditLogService $auditLogService,
        private readonly ResumeTextExtractor $resumeTextExtractor,
    ) {
    }

    public function hrDashboard(Request $request): View
    {
        return view('hr.dashboard', [
            'pageTitle' => 'HR Dashboard',
            'pageHeading' => 'HR Dashboard',
            'activeNav' => 'dashboard',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrDashboardLive(Request $request): JsonResponse
    {
        return response()->json($this->buildHrDashboardData($request));
    }

    public function hrInterviews(Request $request): View
    {
        return view('hr.interviews', [
            'pageTitle' => 'Interviews',
            'pageHeading' => 'Interviews',
            'activeNav' => 'interviews',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrAnalyticsReports(Request $request): View
    {
        return view('hr.analytics-reports', [
            'pageTitle' => 'Analytics & Reports',
            'pageHeading' => 'Analytics & Reports',
            'activeNav' => 'analytics',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrNotifications(Request $request): View
    {
        Notification::query()
            ->where('user_id', (int) ($request->user()?->id ?? 0))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('hr.notifications', [
            'pageTitle' => 'Notifications',
            'pageHeading' => 'Notifications',
            'activeNav' => 'notifications',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrDepartments(Request $request): View
    {
        return view('hr.departments', [
            'pageTitle' => 'Departments',
            'pageHeading' => 'Departments',
            'activeNav' => 'departments',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function applicantDashboard(Request $request): View
    {
        return view('applicant.dashboard', [
            'pageTitle' => 'Applicant Dashboard',
            'pageHeading' => 'Applicant Dashboard',
            'activeNav' => 'dashboard',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantApplications(Request $request): View
    {
        return view('applicant.applications', [
            'pageTitle' => 'My Applications',
            'pageHeading' => 'My Applications',
            'activeNav' => 'applicant.applications',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantRecommendations(Request $request): View
    {
        return view('applicant.recommendations', [
            'pageTitle' => 'AI Recommendations',
            'pageHeading' => 'AI Recommendations',
            'activeNav' => 'applicant.recommendations',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantInterviews(Request $request): View
    {
        return view('applicant.interviews', [
            'pageTitle' => 'Interviews',
            'pageHeading' => 'Interviews',
            'activeNav' => 'applicant.interviews',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantNotifications(Request $request): View
    {
        Notification::query()
            ->where('user_id', (int) ($request->user()?->id ?? 0))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('applicant.notifications', [
            'pageTitle' => 'Notifications',
            'pageHeading' => 'Notifications',
            'activeNav' => 'applicant.notifications',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantDownloads(Request $request): View
    {
        return view('applicant.downloads', [
            'pageTitle' => 'Download Center',
            'pageHeading' => 'Download Center',
            'activeNav' => 'applicant.downloads',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantDashboardLive(Request $request): JsonResponse
    {
        return response()->json($this->buildApplicantDashboardData($request));
    }

    public function toggleSavedJob(Request $request, Job $job): RedirectResponse
    {
        $applicant = $this->resolveApplicant($request);
        $existing = SavedJob::query()
            ->where('applicant_id', $applicant->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existing instanceof SavedJob) {
            $existing->delete();

            return back()->with('success', 'Job removed from saved list.');
        }

        SavedJob::query()->create([
            'applicant_id' => $applicant->id,
            'job_id' => $job->id,
        ]);

        return back()->with('success', 'Job saved successfully.');
    }

    public function downloadApplicationSlip(Request $request, Application $application): Response
    {
        $applicant = $this->resolveApplicant($request);
        abort_if((int) $application->applicant_id !== (int) $applicant->id, 403);

        $content = implode("\n", [
            'Application Slip',
            'Application ID: ' . $application->application_id,
            'Job: ' . ($application->job?->title ?? 'N/A'),
            'Status: ' . strtoupper((string) $application->status),
            'Applied At: ' . optional($application->applied_at)->format('Y-m-d H:i'),
        ]);

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="application-slip-' . $application->application_id . '.txt"');
    }

    public function downloadInterviewInvitation(Request $request, Interview $interview): Response
    {
        $interview->load(['application.job']);
        $applicant = $this->resolveApplicant($request);
        abort_if((int) ($interview->application?->applicant_id ?? 0) !== (int) $applicant->id, 403);

        $data = [
            'interviewId' => (int) $interview->id,
            'jobTitle' => (string) ($interview->application?->job?->title ?? 'N/A'),
            'scheduledAt' => optional($interview->scheduled_at)->format('Y-m-d H:i') ?? 'N/A',
            'mode' => strtoupper((string) $interview->mode),
            'venue' => (string) ($interview->venue ?: 'N/A'),
            'meetingLink' => (string) ($interview->meeting_link ?: 'N/A'),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ];

        if (class_exists('Barryvdh\\DomPDF\\Facade')) {
            $pdf = \Barryvdh\DomPDF\Facade::loadView('exports.interview-invitation-pdf', ['data' => $data]);

            return $pdf->download('interview-invitation-' . $interview->id . '.pdf');
        }

        $html = view('exports.interview-invitation-pdf', ['data' => $data])->render();

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="interview-invitation-' . $interview->id . '.html"');
    }

    public function downloadOfferLetter(Request $request, Application $application): Response
    {
        $applicant = $this->resolveApplicant($request);
        abort_if((int) $application->applicant_id !== (int) $applicant->id, 403);

        $eligible = in_array((string) $application->status, ['shortlisted', 'hired', 'offer_sent'], true);
        abort_unless($eligible, 403, 'Offer letter is not available for this application yet.');

        $content = implode("\n", [
            'Offer Letter (Provisional)',
            'Application ID: ' . $application->application_id,
            'Job: ' . ($application->job?->title ?? 'N/A'),
            'Status: ' . strtoupper((string) $application->status),
            'Generated At: ' . now()->format('Y-m-d H:i'),
        ]);

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="offer-letter-' . $application->application_id . '.txt"');
    }

    public function hrExportCsv(Request $request): StreamedResponse
    {
        $data = $this->buildHrDashboardData($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="hr-dashboard-report.csv"',
        ];

        $callback = function () use ($data): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Metric', 'Value']);
            foreach (($data['metrics'] ?? []) as $label => $value) {
                fputcsv($file, [$label, $value]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Top AI Candidates', 'Score']);
            foreach (($data['topCandidates'] ?? []) as $row) {
                fputcsv($file, [(string) ($row['name'] ?? 'N/A'), (string) ($row['score'] ?? '0') . '%']);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function hrExportExcel(Request $request): Response
    {
        $data = $this->buildHrDashboardData($request);

        $html = view('exports.hr-dashboard-report', ['data' => $data])->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="hr-dashboard-report.xls"');
    }

    public function hrExportPdf(Request $request)
    {
        $data = $this->buildHrDashboardData($request);

        if (class_exists('Barryvdh\\DomPDF\\Facade')) {
            $pdf = \Barryvdh\DomPDF\Facade::loadView('exports.hr-dashboard-report', ['data' => $data]);

            return $pdf->download('hr-dashboard-report.pdf');
        }

        $html = view('exports.hr-dashboard-report', ['data' => $data])->render();

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="hr-dashboard-report.html"');
    }

    public function applicantProfile(Request $request): View
    {
        $applicant = $this->resolveApplicant($request)
            ->load(['educations', 'experiences', 'certificates', 'skills', 'languages', 'applications.job']);

        return view('applicant.profile', [
            'pageTitle' => 'Applicant Profile',
            'pageHeading' => 'Applicant Profile',
            'activeNav' => 'applicant.profile',
            'applicant' => $applicant,
        ]);
    }

    public function updateApplicantProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'national_id' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string'],
            'languages_csv' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'cover_letter' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $applicant = $this->resolveApplicant($request);

        $applicant->update([
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'languages_json' => collect(explode(',', (string) ($validated['languages_csv'] ?? '')))
                ->map(fn (string $language): string => trim($language))
                ->filter(fn (string $language): bool => $language !== '')
                ->values()
                ->all(),
            'profile_completed_at' => now(),
        ]);

        ApplicantLanguage::query()->where('applicant_id', $applicant->id)->delete();
        foreach ((array) ($applicant->languages_json ?? []) as $language) {
            $name = trim((string) $language);
            if ($name !== '') {
                $applicant->languages()->create(['language' => $name]);
            }
        }

        $skillsInput = collect(explode(',', (string) ($validated['skills'] ?? '')))
            ->map(fn (string $skill): string => trim($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->unique()
            ->values();

        $skillIds = $skillsInput->map(function (string $skill): int {
            $model = Skill::query()->firstOrCreate([
                'normalized_name' => strtolower($skill),
            ], [
                'name' => $skill,
            ]);

            return (int) $model->id;
        })->all();

        $applicant->skills()->sync($skillIds);

        if ($request->hasFile('cv')) {
            if (!empty($applicant->cv_path) && Storage::disk('public')->exists($applicant->cv_path)) {
                Storage::disk('public')->delete($applicant->cv_path);
            }

            $cvFile = $request->file('cv');
            $path = $cvFile->store('applicants/cv', 'public');
            $hash = hash_file('sha256', (string) $cvFile->getRealPath()) ?: null;
            $text = $this->resumeTextExtractor->extract($cvFile);

            $applicant->cv_path = $path;
            $applicant->cv_hash = $hash;
            $applicant->cv_text = $text;
            $applicant->save();
        }

        if ($request->hasFile('cover_letter')) {
            if (!empty($applicant->cover_letter_path) && Storage::disk('public')->exists($applicant->cover_letter_path)) {
                Storage::disk('public')->delete($applicant->cover_letter_path);
            }

            $applicant->cover_letter_path = $request->file('cover_letter')->store('applicants/cover-letter', 'public');
            $applicant->save();
        }

        return back()->with('success', 'Applicant profile updated successfully.');
    }

    public function applicantJobs(Request $request): View
    {
        $departmentMap = $this->recruitmentDepartmentMap();
        $orderedDepartmentNames = array_keys($departmentMap);

        foreach ($departmentMap as $name => $code) {
            Department::query()->firstOrCreate(
                ['name' => $name],
                ['code' => $code],
            );
        }

        $departmentCategories = Department::query()
            ->whereIn('name', $orderedDepartmentNames)
            ->withCount([
                'jobs as available_jobs_count' => fn ($query) => $query
                    ->where('status', 'published')
                    ->where('application_deadline', '>=', now()),
            ])
            ->get(['id', 'name'])
            ->sortBy(fn (Department $department): int => (int) array_search($department->name, $orderedDepartmentNames, true))
            ->values();

        $totalJobs = Job::query()
            ->where('status', 'published')
            ->where('application_deadline', '>=', now())
            ->count();

        return view('applicant.jobs', [
            'pageTitle' => 'Browse Jobs',
            'pageHeading' => 'Browse Jobs',
            'activeNav' => 'applicant.jobs',
            'totalJobs' => $totalJobs,
            'departmentCategories' => $departmentCategories,
        ]);
    }

    public function applicantDepartmentJobs(Request $request, Department $department): View
    {
        $search = trim((string) $request->query('q', ''));

        $jobs = Job::query()
            ->with(['hrOfficer:id,name', 'department:id,name'])
            ->where('status', 'published')
            ->where('application_deadline', '>=', now())
            ->where('department_id', $department->id)
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%' . $search . '%';
                $query->where(function ($nested) use ($needle): void {
                    $nested->where('title', 'like', $needle)
                        ->orWhere('description', 'like', $needle)
                        ->orWhere('qualifications', 'like', $needle);
                });
            })
            ->orderByDesc('created_at')
            ->orderBy('application_deadline')
            ->paginate(12)
            ->withQueryString();

        $applicant = $this->resolveApplicant($request);
        $appliedJobIds = $applicant->applications()->pluck('job_id')->all();
        $savedJobIds = $applicant->savedJobs()->pluck('job_id')->all();

        return view('applicant.jobs-department', [
            'pageTitle' => $department->name . ' Vacancies',
            'pageHeading' => $department->name . ' Vacancies',
            'activeNav' => 'applicant.jobs',
            'department' => $department,
            'jobs' => $jobs,
            'appliedJobIds' => $appliedJobIds,
            'savedJobIds' => $savedJobIds,
            'search' => $search,
        ]);
    }

    public function applicantJobShow(Request $request, Job $job): View
    {
        abort_unless($job->status === 'published', 404, 'Job is not available.');

        $applicant = $this->resolveApplicant($request);
        $alreadyApplied = Application::query()
            ->where('job_id', $job->id)
            ->where('applicant_id', $applicant->id)
            ->exists();

        $isSaved = SavedJob::query()
            ->where('applicant_id', $applicant->id)
            ->where('job_id', $job->id)
            ->exists();

        return view('applicant.job-detail', [
            'pageTitle' => 'Job Requirements',
            'pageHeading' => 'Job Requirements',
            'activeNav' => 'applicant.jobs',
            'job' => $job,
            'alreadyApplied' => $alreadyApplied,
            'isSaved' => $isSaved,
        ]);
    }

    public function applicantApplyForm(Request $request, Job $job): View
    {
        abort_unless($job->status === 'published', 404, 'Job is not available.');

        $applicant = $this->resolveApplicant($request);
        $alreadyApplied = Application::query()
            ->where('job_id', $job->id)
            ->where('applicant_id', $applicant->id)
            ->exists();

        return view('applicant.apply', [
            'pageTitle' => 'Apply Job',
            'pageHeading' => 'Apply Job',
            'activeNav' => 'applicant.jobs',
            'job' => $job,
            'applicant' => $applicant,
            'alreadyApplied' => $alreadyApplied,
        ]);
    }

    public function applicantApplySubmit(Request $request, Job $job): RedirectResponse
    {
        $validated = $request->validate([
            'cover_letter_text' => ['nullable', 'string'],
        ]);

        if ($job->status !== 'published' || now()->gt($job->application_deadline)) {
            return back()->with('error', 'This vacancy is closed.');
        }

        $applicant = $this->resolveApplicant($request)->load(['skills', 'educations', 'experiences', 'certificates']);

        if (empty($applicant->cv_path)) {
            return back()->with('error', 'Upload CV in your profile before applying.');
        }

        $alreadyApplied = Application::query()
            ->where('job_id', $job->id)
            ->where('applicant_id', $applicant->id)
            ->exists();

        if ($alreadyApplied) {
            return back()->with('error', 'You already applied for this vacancy.');
        }

        $application = Application::query()->create([
            'application_id' => 'APP-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'job_id' => $job->id,
            'applicant_id' => $applicant->id,
            'cover_letter_text' => $validated['cover_letter_text'] ?? null,
            'status' => 'ai_processing',
            'duplicate_hash' => hash('sha256', $job->id . '|' . $applicant->id . '|' . (string) ($applicant->cv_hash ?? '')),
            'applied_at' => now(),
        ]);

        ProcessApplicationAiScore::dispatch((int) $application->id);

        $receivedTemplate = $this->candidateCommunicationService->applicationReceived($application->loadMissing('job'));

        $this->notificationService->notify(
            (int) $request->user()->id,
            $receivedTemplate['type'],
            $receivedTemplate['title'],
            $receivedTemplate['message'],
            $application->id
        );

        $this->notificationService->notify(
            (int) $job->hr_officer_id,
            'new_application',
            'New application received',
            'A new application was submitted for ' . $job->title . '.',
            $application->id
        );

        $this->auditLogService->log(
            (int) $request->user()->id,
            'application.created.web',
            Application::class,
            (int) $application->id,
            null,
            array_merge($application->toArray(), [
                'ai_processing' => true,
                'final_submission_status' => 'ai_processing',
            ]),
            $request
        );

        return redirect()->route('applicant.jobs')->with('success', 'Application submitted successfully. Status: AI PROCESSING. HR will review after AI scoring completes.');
    }

    public function applicantChatbotUploadCv(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $applicant = $this->resolveApplicant($request);

        if (!empty($applicant->cv_path) && Storage::disk('public')->exists($applicant->cv_path)) {
            Storage::disk('public')->delete($applicant->cv_path);
        }

        $cvFile = $validated['cv'];
        $path = $cvFile->store('applicants/cv', 'public');
        $hash = hash_file('sha256', (string) $cvFile->getRealPath()) ?: null;
        $text = $this->resumeTextExtractor->extract($cvFile);

        $applicant->cv_path = $path;
        $applicant->cv_hash = $hash;
        $applicant->cv_text = $text;
        $applicant->profile_completed_at = $applicant->profile_completed_at ?? now();
        $applicant->save();

        $this->notificationService->notify(
            (int) $request->user()->id,
            'cv_uploaded',
            'CV uploaded successfully',
            'Your CV has been uploaded from AI Support chat and is now ready for AI recommendations.',
            (int) $applicant->id
        );

        return redirect()->route('applicant.recommendations')->with('success', 'CV uploaded successfully from AI Support chat. Check your AI recommendations below.');
    }

    public function applicantRespondInterview(Request $request, Interview $interview): RedirectResponse
    {
        $validated = $request->validate([
            'response' => ['required', 'string', Rule::in(['accept', 'reject'])],
        ]);

        $interview->loadMissing(['application.job', 'application.applicant']);
        $applicant = $this->resolveApplicant($request);

        abort_if((int) ($interview->application?->applicant_id ?? 0) !== (int) $applicant->id, 403, 'Not authorized for this interview.');

        $currentStatus = strtolower((string) $interview->status);
        if (in_array($currentStatus, ['completed', 'cancelled', 'no_show'], true)) {
            return back()->with('error', 'This interview is no longer open for response.');
        }

        $accepted = $validated['response'] === 'accept';
        $interview->update([
            'status' => $accepted ? 'confirmed' : 'cancelled',
            'result_notes' => $accepted
                ? 'Applicant accepted interview invitation.'
                : 'Applicant rejected interview invitation.',
        ]);

        if ($accepted) {
            $interview->application?->update(['status' => 'interview_scheduled']);
        } else {
            $interview->application?->update(['status' => 'review']);
        }

        $hrOfficerId = (int) ($interview->application?->job?->hr_officer_id ?? 0);
        if ($hrOfficerId > 0) {
            $this->notificationService->notify(
                $hrOfficerId,
                'interview_response',
                'Interview response received',
                ($interview->application?->applicant?->user?->name ?? 'Applicant') . ' has ' . ($accepted ? 'accepted' : 'rejected') . ' interview invitation.',
                (int) $interview->id
            );
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'interview.responded.web',
            Interview::class,
            (int) $interview->id,
            null,
            [
                'response' => $validated['response'],
                'status' => $interview->status,
            ],
            $request
        );

        return back()->with('success', $accepted ? 'Interview accepted successfully.' : 'Interview declined successfully.');
    }

    public function applicantRespondOffer(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'response' => ['required', 'string', Rule::in(['accept', 'reject'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $applicant = $this->resolveApplicant($request);
        $application->loadMissing(['applicant.user', 'job']);

        abort_if((int) $application->applicant_id !== (int) $applicant->id, 403, 'Not authorized for this offer response.');

        if ((string) ($application->offer_status ?? '') !== 'pending') {
            return back()->with('error', 'This offer is not awaiting your response.');
        }

        $accepted = $validated['response'] === 'accept';

        $application->update([
            'offer_status' => $accepted ? 'accepted' : 'rejected',
            'offer_response_at' => now(),
            'status' => $accepted ? 'hired' : 'offer_declined',
            'onboarding_status' => $accepted ? 'not_started' : null,
        ]);

        $hrOfficerId = (int) ($application->job?->hr_officer_id ?? 0);
        if ($hrOfficerId > 0) {
            $this->notificationService->notify(
                $hrOfficerId,
                'offer_response',
                'Offer response received',
                ($application->applicant?->user?->name ?? 'Applicant') . ' has ' . ($accepted ? 'accepted' : 'rejected') . ' the offer for ' . ($application->job?->title ?? 'this role') . '.',
                (int) $application->id
            );
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'offer.responded.web',
            Application::class,
            (int) $application->id,
            null,
            [
                'response' => $validated['response'],
                'notes' => $validated['notes'] ?? null,
                'status' => $application->status,
                'offer_status' => $application->offer_status,
            ],
            $request
        );

        return back()->with('success', $accepted ? 'Offer accepted successfully.' : 'Offer declined successfully.');
    }

    public function hrUpdateApplicationStatus(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['review', 'shortlisted', 'interview_scheduled', 'offer_sent', 'hired', 'onboarding_completed', 'placed', 'offer_declined', 'rejected'])],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing(['applicant.user', 'job']);
        $before = $application->toArray();

        $application->update(['status' => $validated['status']]);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        abort_if($applicantUserId <= 0, 404, 'Applicant user not found.');

        $statusTemplate = $this->candidateCommunicationService->fromStatus(
            $application,
            (string) $validated['status'],
            $validated['message'] ?? null
        );

        $this->notificationService->notify(
            $applicantUserId,
            $statusTemplate['type'],
            $statusTemplate['title'],
            $statusTemplate['message'],
            (int) $application->id
        );

        $this->auditLogService->log(
            (int) $request->user()->id,
            'application.status_updated.web',
            Application::class,
            (int) $application->id,
            $before,
            $application->fresh()?->toArray(),
            $request
        );

        return back()->with('success', 'Application status updated to ' . strtoupper($validated['status']) . '.');
    }

    public function hrShortlistCandidate(Request $request, Application $application): RedirectResponse
    {
        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing('applicant');
        $before = $application->toArray();

        $application->update(['status' => 'shortlisted']);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        abort_if($applicantUserId <= 0, 404, 'Applicant user not found.');

        $statusTemplate = $this->candidateCommunicationService->shortlisted($application);

        $this->notificationService->notify(
            $applicantUserId,
            $statusTemplate['type'],
            $statusTemplate['title'],
            $statusTemplate['message'],
            (int) $application->id
        );

        $this->auditLogService->log(
            (int) $request->user()->id,
            'application.shortlisted.web',
            Application::class,
            (int) $application->id,
            $before,
            $application->fresh()?->toArray(),
            $request
        );

        return back()->with('success', 'Candidate shortlisted successfully.');
    }

    public function hrScheduleInterview(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', 'string', Rule::in(['online', 'physical'])],
            'meeting_link' => ['nullable', 'url'],
            'venue' => ['nullable', 'string', 'max:255'],
            'send_email_invite' => ['nullable', 'boolean'],
        ]);

        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing('applicant');

        $hasActiveInterview = $application->interviews()
            ->whereIn('status', ['scheduled', 'invitation_sent', 'confirmed'])
            ->exists();

        if ($hasActiveInterview) {
            return back()->with('error', 'This candidate already has an active interview.');
        }

        $interview = Interview::query()->create([
            'application_id' => (int) $application->id,
            'interviewer_id' => (int) $request->user()->id,
            'scheduled_at' => $validated['scheduled_at'],
            'mode' => $validated['mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => 'scheduled',
        ]);

        $application->update(['status' => 'interview_scheduled']);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        abort_if($applicantUserId <= 0, 404, 'Applicant user not found.');
        $sendEmailInvite = (bool) ($validated['send_email_invite'] ?? false);

        $statusTemplate = $this->candidateCommunicationService->interviewScheduled($application->loadMissing('job'), $interview);

        $this->notificationService->notify(
            $applicantUserId,
            $statusTemplate['type'],
            $statusTemplate['title'],
            $statusTemplate['message'],
            (int) $interview->id,
            false,
            true
        );

        if ($sendEmailInvite) {
            $applicantEmail = (string) ($application->applicant?->user?->email ?? '');
            if ($applicantEmail !== '') {
                $this->outboundChannelService->sendEmail(
                    $applicantEmail,
                    'Interview Invitation - ' . (string) ($application->job?->title ?? 'Recruitment Interview'),
                    $statusTemplate['message']
                );
            }
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'interview.scheduled.web',
            Interview::class,
            (int) $interview->id,
            null,
            $interview->toArray(),
            $request
        );

        return back()->with('success', 'Interview scheduled successfully.' . ($sendEmailInvite ? ' Invitation email sent.' : ' Email invitation was not sent.'));
    }

    public function hrSendOffer(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'offer_message' => ['nullable', 'string', 'max:2000'],
            'send_email_offer' => ['nullable', 'boolean'],
        ]);

        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing(['applicant.user', 'job']);

        if (in_array((string) $application->status, ['rejected', 'offer_declined', 'placed'], true)) {
            return back()->with('error', 'Cannot send offer for this application status.');
        }

        $defaultMessage = 'We are pleased to offer you a position for ' . ($application->job?->title ?? 'this role') . '. Please accept or reject the offer from your applicant portal.';
        $offerMessage = trim((string) ($validated['offer_message'] ?? ''));
        if ($offerMessage === '') {
            $offerMessage = $defaultMessage;
        }

        $application->update([
            'status' => 'offer_sent',
            'offer_status' => 'pending',
            'offer_sent_at' => now(),
            'offer_response_at' => null,
            'offer_message' => $offerMessage,
            'placement_status' => $application->placement_status ?: 'open',
        ]);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        abort_if($applicantUserId <= 0, 404, 'Applicant user not found.');

        $statusTemplate = $this->candidateCommunicationService->offerSent($application->loadMissing('job'));

        $this->notificationService->notify(
            $applicantUserId,
            $statusTemplate['type'],
            $statusTemplate['title'],
            $statusTemplate['message'],
            (int) $application->id,
            false,
            true
        );

        if ((bool) ($validated['send_email_offer'] ?? false)) {
            $applicantEmail = (string) ($application->applicant?->user?->email ?? '');
            if ($applicantEmail !== '') {
                $this->outboundChannelService->sendEmail(
                    $applicantEmail,
                    'Job Offer - ' . (string) ($application->job?->title ?? 'Recruitment Offer'),
                    $statusTemplate['message']
                );
            }
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'offer.sent.web',
            Application::class,
            (int) $application->id,
            null,
            [
                'status' => $application->status,
                'offer_status' => $application->offer_status,
                'offer_sent_at' => optional($application->offer_sent_at)->toIso8601String(),
            ],
            $request
        );

        return back()->with('success', 'Offer sent successfully.' . ((bool) ($validated['send_email_offer'] ?? false) ? ' Offer email sent.' : ' Offer email was not sent.'));
    }

    public function hrUpdateOnboarding(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'onboarding_status' => ['required', 'string', Rule::in(['not_started', 'in_progress', 'completed'])],
            'onboarding_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing(['applicant', 'job']);

        if ((string) ($application->offer_status ?? '') !== 'accepted' && (string) $application->status !== 'hired') {
            return back()->with('error', 'Onboarding can only be updated after offer acceptance.');
        }

        $onboardingStatus = (string) $validated['onboarding_status'];

        $update = [
            'onboarding_status' => $onboardingStatus,
            'onboarding_notes' => $validated['onboarding_notes'] ?? null,
            'status' => $onboardingStatus === 'completed' ? 'onboarding_completed' : 'hired',
        ];

        if ($onboardingStatus === 'in_progress' && $application->onboarding_started_at === null) {
            $update['onboarding_started_at'] = now();
        }

        if ($onboardingStatus === 'completed') {
            $update['onboarding_completed_at'] = now();
            $update['placement_status'] = $application->placement_status ?: 'open';
        }

        $application->update($update);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        if ($applicantUserId > 0) {
            $this->notificationService->notify(
                $applicantUserId,
                'onboarding_updated',
                'Onboarding update',
                'Your onboarding status for ' . ($application->job?->title ?? 'this role') . ' is now: ' . strtoupper(str_replace('_', ' ', $onboardingStatus)) . '.',
                (int) $application->id
            );
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'onboarding.updated.web',
            Application::class,
            (int) $application->id,
            null,
            [
                'onboarding_status' => $application->onboarding_status,
                'onboarding_started_at' => optional($application->onboarding_started_at)->toIso8601String(),
                'onboarding_completed_at' => optional($application->onboarding_completed_at)->toIso8601String(),
            ],
            $request
        );

        return back()->with('success', 'Onboarding status updated successfully.');
    }

    public function hrClosePlacement(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'placement_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->ensureHrApplicationAccess($request, $application);
        $application->loadMissing(['applicant', 'job']);

        if ((string) ($application->onboarding_status ?? '') !== 'completed') {
            return back()->with('error', 'Placement can be closed only after onboarding is completed.');
        }

        $application->update([
            'status' => 'placed',
            'placement_status' => 'closed',
            'placement_closed_at' => now(),
            'placement_notes' => $validated['placement_notes'] ?? null,
        ]);

        $applicantUserId = (int) ($application->applicant?->user_id ?? 0);
        if ($applicantUserId > 0) {
            $this->notificationService->notify(
                $applicantUserId,
                'placement_closed',
                'Placement completed',
                'Your recruitment journey for ' . ($application->job?->title ?? 'this role') . ' has been successfully closed.',
                (int) $application->id
            );
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'placement.closed.web',
            Application::class,
            (int) $application->id,
            null,
            [
                'status' => $application->status,
                'placement_status' => $application->placement_status,
                'placement_closed_at' => optional($application->placement_closed_at)->toIso8601String(),
            ],
            $request
        );

        return back()->with('success', 'Placement closed successfully.');
    }

    public function hrUpdateInterviewResult(Request $request, Interview $interview): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['confirmed', 'completed', 'cancelled', 'no_show'])],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result_notes' => ['nullable', 'string'],
            'final_decision' => ['nullable', 'string', Rule::in(['review', 'shortlisted', 'hired', 'rejected'])],
        ]);

        $this->ensureHrInterviewAccess($request, $interview);
        $before = $interview->toArray();

        $interview->update([
            'status' => $validated['status'],
            'interview_score' => $validated['interview_score'] ?? null,
            'result_notes' => $validated['result_notes'] ?? null,
        ]);

        if (!empty($validated['final_decision'])) {
            $interview->loadMissing('application.applicant');
            $interview->application?->update(['status' => $validated['final_decision']]);

            $finalMessage = match ($validated['final_decision']) {
                'hired' => 'Congratulations, you have been selected.',
                'rejected' => 'Thank you for applying. Your application was not successful this time.',
                'shortlisted' => 'Your application remains shortlisted for the next step.',
                default => 'Your application is under further review.',
            };

            $applicantUserId = (int) ($interview->application?->applicant?->user_id ?? 0);
            abort_if($applicantUserId <= 0, 404, 'Applicant user not found.');

            $this->notificationService->notify(
                $applicantUserId,
                'final_recruitment_result',
                'Application update',
                $finalMessage,
                (int) $interview->application_id
            );
        }

        $this->auditLogService->log(
            (int) $request->user()->id,
            'interview.updated.web',
            Interview::class,
            (int) $interview->id,
            $before,
            $interview->fresh()?->toArray(),
            $request
        );

        return back()->with('success', 'Interview result updated successfully.');
    }

    public function hrCandidateRanking(Request $request): View
    {
        $role = strtolower((string) $request->user()->role);
        $isAdmin = in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);

        $jobs = Job::query()
            ->when(!$isAdmin, fn ($query) => $query->where('hr_officer_id', (int) $request->user()->id))
            ->latest()
            ->get(['id', 'title']);

        $selectedJobId = (int) $request->query('job_id', 0);
        if ($selectedJobId === 0 && $jobs->isNotEmpty()) {
            $selectedJobId = (int) $jobs->first()->id;
        }

        $query = Application::query()
            ->with(['applicant.user', 'applicant.skills', 'applicant.educations', 'applicant.experiences', 'aiScore', 'job'])
            ->when(
                !$isAdmin,
                fn ($applicationQuery) => $applicationQuery->whereHas('job', fn ($jobQuery) => $jobQuery->where('hr_officer_id', (int) $request->user()->id))
            );

        if ($selectedJobId > 0) {
            $query->where('job_id', $selectedJobId);
        }

        if ($request->filled('skill')) {
            $needle = '%' . strtolower((string) $request->query('skill')) . '%';
            $query->whereHas('applicant.skills', fn ($skillQuery) => $skillQuery->whereRaw('LOWER(name) like ?', [$needle]));
        }

        if ($request->filled('degree')) {
            $needle = '%' . strtolower((string) $request->query('degree')) . '%';
            $query->whereHas('applicant.educations', fn ($eduQuery) => $eduQuery->whereRaw('LOWER(level) like ?', [$needle]));
        }

        if ($request->filled('score_min')) {
            $query->whereHas('aiScore', fn ($scoreQuery) => $scoreQuery->where('match_percentage', '>=', (float) $request->query('score_min')));
        }

        $applications = $query
            ->orderByDesc(
                AiScore::query()
                    ->select('match_percentage')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->paginate(15)
            ->withQueryString();

        return view('hr.candidate-ranking', [
            'pageTitle' => 'Candidate Ranking',
            'pageHeading' => 'Candidate Ranking',
            'activeNav' => 'hr.ranking',
            'jobs' => $jobs,
            'selectedJobId' => $selectedJobId,
            'applications' => $applications,
        ]);
    }

    public function hrApplicationCv(Request $request, Application $application): BinaryFileResponse|RedirectResponse
    {
        $this->ensureHrApplicationAccess($request, $application);

        $application->loadMissing(['applicant.user', 'job']);
        $cvPath = (string) ($application->applicant?->cv_path ?? '');

        if ($cvPath === '' || !Storage::disk('public')->exists($cvPath)) {
            return back()->with('error', 'CV file not found for this applicant.');
        }

        $absolutePath = Storage::disk('public')->path($cvPath);
        $extension = strtolower((string) pathinfo($cvPath, PATHINFO_EXTENSION));
        $applicantName = (string) ($application->applicant?->user?->name ?? 'candidate');
        $jobTitle = (string) ($application->job?->title ?? 'vacancy');
        $safeFileName = Str::slug($applicantName . '-' . $jobTitle . '-' . (string) $application->application_id);
        $downloadName = ($safeFileName !== '' ? $safeFileName : 'candidate-cv') . ($extension !== '' ? '.' . $extension : '');

        $disposition = strtolower((string) $request->query('disposition', 'download'));
        if ($disposition === 'inline' && $extension === 'pdf') {
            return response()->file($absolutePath, [
                'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
            ]);
        }

        return response()->download($absolutePath, $downloadName);
    }

    public function hrJobs(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $jobs = Job::query()
            ->withCount('applications')
            ->with(['department:id,name'])
            ->where('hr_officer_id', (int) $request->user()->id)
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%' . $search . '%';
                $query->where(function ($nested) use ($needle): void {
                    $nested->where('title', 'like', $needle)
                        ->orWhere('description', 'like', $needle);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('hr.jobs.index', [
            'pageTitle' => 'Job Management',
            'pageHeading' => 'Job Management',
            'activeNav' => 'hr.jobs',
            'jobs' => $jobs,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function hrJobsCreate(): View
    {
        return view('hr.jobs.create', [
            'pageTitle' => 'Create Vacancy',
            'pageHeading' => 'Create Vacancy',
            'activeNav' => 'hr.jobs',
            'departments' => $this->recruiterDepartments(),
        ]);
    }

    public function hrJobsStore(Request $request): RedirectResponse
    {
        $validated = $this->validateHrJob($request, false);

        $job = Job::query()->create([
            'department_id' => $validated['department_id'] ?? null,
            'hr_officer_id' => (int) $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'qualifications' => $validated['qualifications'] ?? null,
            'duties' => $validated['duties'] ?? null,
            'experience_level' => $validated['experience_level'],
            'min_years_experience' => (int) $validated['min_years_experience'],
            'application_deadline' => $validated['application_deadline'],
            'positions' => (int) $validated['positions'],
            'status' => $validated['status'],
            'required_skills_json' => $this->parseSkillsCsv((string) ($validated['required_skills_csv'] ?? '')),
        ]);

        $this->syncJobSkills($job);

        $this->auditLogService->log(
            (int) $request->user()->id,
            'job.created.web',
            Job::class,
            (int) $job->id,
            null,
            $job->toArray(),
            $request
        );

        return redirect()->route('hr.jobs.index')->with('success', 'Vacancy created successfully.');
    }

    public function hrJobsEdit(Request $request, Job $job): View
    {
        $this->ensureHrJobAccess($request, $job);

        return view('hr.jobs.edit', [
            'pageTitle' => 'Edit Vacancy',
            'pageHeading' => 'Edit Vacancy',
            'activeNav' => 'hr.jobs',
            'job' => $job,
            'departments' => $this->recruiterDepartments(),
        ]);
    }

    private function recruiterDepartments()
    {
        $departmentMap = $this->recruitmentDepartmentMap();

        foreach ($departmentMap as $name => $code) {
            Department::query()->firstOrCreate(
                ['name' => $name],
                ['code' => $code],
            );
        }

        $orderedNames = array_keys($departmentMap);

        return Department::query()
            ->whereIn('name', $orderedNames)
            ->get(['id', 'name'])
            ->sortBy(fn (Department $department): int => (int) array_search($department->name, $orderedNames, true))
            ->values();
    }

    private function recruitmentDepartmentMap(): array
    {
        return [
            'Accounting and Auditing' => 'ACC_AUD',
            'ACSE' => 'ACSE',
            'Agricultural and Natural Resources' => 'AGR_NR',
            'Banking, Economics and Financial Services' => 'BANK_ECO_FIN',
            'Climate Change' => 'CLIMATE',
            'Creative and Design' => 'CREATIVE_DESIGN',
            'CSE' => 'CSE',
            "Driver's" => 'DRIVERS',
            'Education and Training' => 'EDU',
            'Engineering and Construction' => 'ENG_CONST',
            'Environmental Sciences and Geography' => 'ENV_GEO',
            'Farming and Livestock' => 'FARM_LIVE',
            'Healthcare and Pharmaceutical' => 'HEALTH_PHARMA',
            'HR & Administration' => 'HR_ADMIN',
            'International Relations' => 'INT_REL',
            'IT and Telecoms' => 'IT_TELECOM',
            'Land Management' => 'LAND_MGMT',
            'Legal' => 'LEGAL',
            'Linguistics' => 'LINGUISTICS',
            'Manufacturing' => 'MANUFACTURING',
            'Marketing,Media and Brand' => 'MKT_MEDIA_BRAND',
            'Physical & Natural Sciences' => 'PHYS_NAT_SCI',
            'Procurement & Logistic Management' => 'PROC_LOG_MGMT',
            'Project, Planning and Policy Management' => 'PROJ_PLAN_POLICY',
            'Religious Studies' => 'REL_STUDIES',
            'Research,Science and Biotech' => 'RES_SCI_BIO',
            'Security' => 'SECURITY',
            'Sociology, Political Science, Community and Social Development' => 'SOC_POL_COMM',
            'Statistics and Mathematics' => 'STATS_MATH',
            'Taxation and Social Protection' => 'TAX_SOC_PROT',
            'Tourism and Travel' => 'TOURISM_TRAVEL',
            'Trades and Services' => 'TRADES_SERV',
            'Transport and Logistics' => 'TRANSPORT_LOG',
            'Water, Mining and Natural Resources' => 'WATER_MINING_NR',
        ];
    }

    public function hrJobsUpdate(Request $request, Job $job): RedirectResponse
    {
        $this->ensureHrJobAccess($request, $job);
        $validated = $this->validateHrJob($request, true);

        $oldValues = $job->toArray();

        $job->update([
            'department_id' => $validated['department_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'qualifications' => $validated['qualifications'] ?? null,
            'duties' => $validated['duties'] ?? null,
            'experience_level' => $validated['experience_level'],
            'min_years_experience' => (int) $validated['min_years_experience'],
            'application_deadline' => $validated['application_deadline'],
            'positions' => (int) $validated['positions'],
            'status' => $validated['status'],
            'required_skills_json' => $this->parseSkillsCsv((string) ($validated['required_skills_csv'] ?? '')),
        ]);

        $this->syncJobSkills($job);

        $this->auditLogService->log(
            (int) $request->user()->id,
            'job.updated.web',
            Job::class,
            (int) $job->id,
            $oldValues,
            $job->fresh()?->toArray(),
            $request
        );

        return redirect()->route('hr.jobs.index')->with('success', 'Vacancy updated successfully.');
    }

    public function hrJobsDelete(Request $request, Job $job): RedirectResponse
    {
        $this->ensureHrJobAccess($request, $job);

        if ($job->applications()->exists()) {
            return back()->with('error', 'Cannot delete a vacancy with submitted applications. Close it instead.');
        }

        $oldValues = $job->toArray();
        $job->delete();

        $this->auditLogService->log(
            (int) $request->user()->id,
            'job.deleted.web',
            Job::class,
            (int) $job->id,
            $oldValues,
            null,
            $request
        );

        return redirect()->route('hr.jobs.index')->with('success', 'Vacancy deleted successfully.');
    }

    private function resolveApplicant(Request $request): Applicant
    {
        return Applicant::query()->firstOrCreate([
            'user_id' => (int) $request->user()->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateHrJob(Request $request, bool $isUpdate): array
    {
        $baseRules = [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'qualifications' => ['nullable', 'string'],
            'duties' => ['nullable', 'string'],
            'required_skills_csv' => ['nullable', 'string'],
            'experience_level' => ['required', 'string', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'min_years_experience' => ['required', 'integer', 'min:0', 'max:50'],
            'application_deadline' => ['required', 'date'],
            'positions' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'closed'])],
        ];

        $baseRules['application_deadline'][] = $isUpdate ? 'after_or_equal:today' : 'after:now';

        return $request->validate($baseRules);
    }

    /**
     * @return array<int, string>
     */
    private function parseSkillsCsv(string $skillsCsv): array
    {
        return collect(explode(',', $skillsCsv))
            ->map(fn (string $skill): string => trim($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function syncJobSkills(Job $job): void
    {
        $skills = collect((array) ($job->required_skills_json ?? []));

        $skillIds = $skills->map(function (string $skill): int {
            $normalized = strtolower(trim($skill));
            $model = Skill::query()->firstOrCreate(
                ['normalized_name' => $normalized],
                ['name' => $skill]
            );

            return (int) $model->id;
        })->all();

        $job->skills()->sync($skillIds);
    }

    private function ensureHrJobAccess(Request $request, Job $job): void
    {
        $role = strtolower((string) $request->user()->role);
        $isAdmin = in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);

        if ($isAdmin) {
            return;
        }

        abort_if((int) $job->hr_officer_id !== (int) $request->user()->id, 403, 'You are not authorized for this vacancy.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHrDashboardData(Request $request): array
    {
        $role = strtolower((string) $request->user()->role);
        $isAdmin = in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);

        $jobsQuery = Job::query()->when(!$isAdmin, fn ($query) => $query->where('hr_officer_id', (int) $request->user()->id));
        $applicationsQuery = Application::query()->whereHas(
            'job',
            fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id))
        );

        $totalApplications = (clone $applicationsQuery)->count();
        $aiCompleted = (clone $applicationsQuery)->whereHas('aiScore')->count();
        $totalApplicants = (clone $applicationsQuery)->distinct('applicant_id')->count('applicant_id');

        $metrics = [
            'Total Applicants' => $totalApplicants,
            'Open Vacancies' => (clone $jobsQuery)->where('status', 'published')->where('application_deadline', '>=', now())->count(),
            'Shortlisted' => (clone $applicationsQuery)->where(function ($query): void {
                $query->where('status', 'shortlisted')
                    ->orWhereHas('aiScore', fn ($scoreQuery) => $scoreQuery->whereIn('recommendation_level', ['Shortlisted', 'Highly Qualified']));
            })->count(),
            'Pending Applications' => (clone $applicationsQuery)->whereIn('status', ['submitted', 'pending', 'review', 'under_review', 'ai_processing'])->count(),
            'AI Completed' => $aiCompleted,
            'AI Total' => $totalApplications,
        ];

        $perJob = (clone $jobsQuery)
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(8)
            ->get(['id', 'title']);

        $dailyMap = [];
        $dailyLabels = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->toDateString();
            $dailyMap[$key] = 0;
            $dailyLabels[] = $date->format('M d');
        }

        $trendRows = (clone $applicationsQuery)
            ->where('applied_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['applied_at']);

        foreach ($trendRows as $row) {
            if (!$row->applied_at) {
                continue;
            }

            $key = Carbon::parse($row->applied_at)->toDateString();
            if (array_key_exists($key, $dailyMap)) {
                $dailyMap[$key]++;
            }
        }

        $currentWeek = (clone $applicationsQuery)
            ->where('applied_at', '>=', now()->startOfWeek())
            ->count();
        $previousWeek = (clone $applicationsQuery)
            ->whereBetween('applied_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $weeklyGrowth = $previousWeek > 0
            ? round((($currentWeek - $previousWeek) / $previousWeek) * 100, 1)
            : ($currentWeek > 0 ? 100.0 : 0.0);

        $qualificationRows = Education::query()
            ->whereHas('applicant.applications.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->get(['level']);

        $qualificationMap = [
            'Degree' => 0,
            'Diploma' => 0,
            'Masters' => 0,
            'Other' => 0,
        ];

        foreach ($qualificationRows as $row) {
            $level = strtolower((string) $row->level);
            if (str_contains($level, 'master')) {
                $qualificationMap['Masters']++;
            } elseif (str_contains($level, 'diploma')) {
                $qualificationMap['Diploma']++;
            } elseif (str_contains($level, 'bachelor') || str_contains($level, 'degree')) {
                $qualificationMap['Degree']++;
            } else {
                $qualificationMap['Other']++;
            }
        }

        $genderRows = Applicant::query()
            ->whereHas('applications.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->get(['gender']);

        $genderMap = [
            'Male' => 0,
            'Female' => 0,
            'Other/Unknown' => 0,
        ];

        foreach ($genderRows as $row) {
            $gender = strtolower((string) $row->gender);
            if ($gender === 'male') {
                $genderMap['Male']++;
            } elseif ($gender === 'female') {
                $genderMap['Female']++;
            } else {
                $genderMap['Other/Unknown']++;
            }
        }

        $departmentRows = Job::query()
            ->selectRaw('department_id, COUNT(applications.id) as applicants_count')
            ->join('applications', 'applications.job_id', '=', 'jobs.id')
            ->when(!$isAdmin, fn ($query) => $query->where('jobs.hr_officer_id', (int) $request->user()->id))
            ->groupBy('department_id')
            ->get();

        $departmentStats = $departmentRows->map(function ($row): array {
            $department = Department::query()->find($row->department_id);

            return [
                'department' => $department?->name ?? 'Unassigned',
                'count' => (int) $row->applicants_count,
            ];
        })->values()->all();

        $recentApplications = (clone $applicationsQuery)
            ->with(['applicant.user', 'job'])
            ->latest('applied_at')
            ->limit(8)
            ->get()
            ->map(fn ($application): array => [
                'candidate' => (string) ($application->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                'at' => optional($application->applied_at)->format('Y-m-d H:i'),
            ])
            ->all();

        $upcomingInterviews = Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn ($interview): array => [
                'id' => (int) $interview->id,
                'candidate' => (string) ($interview->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('Y-m-d H:i'),
                'mode' => strtoupper((string) $interview->mode),
                'venue' => (string) ($interview->venue ?? 'N/A'),
                'meeting_link' => (string) ($interview->meeting_link ?? ''),
                'status' => (string) ($interview->status ?? 'scheduled'),
            ])
            ->all();

        $todayInterviews = Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn ($interview): array => [
                'id' => (int) $interview->id,
                'candidate' => (string) ($interview->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('H:i'),
                'status' => (string) ($interview->status ?? 'scheduled'),
            ])
            ->all();

        $interviewStatusRows = Interview::query()
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $interviewStatusMap = [
            'scheduled' => 0,
            'invitation_sent' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($interviewStatusRows as $row) {
            $status = strtolower((string) $row->status);
            $count = (int) $row->total;

            if (array_key_exists($status, $interviewStatusMap)) {
                $interviewStatusMap[$status] += $count;
            } elseif ($status === 'sent') {
                $interviewStatusMap['invitation_sent'] += $count;
            }
        }

        $topCandidates = AiScore::query()
            ->with(['application.applicant.user'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->orderByDesc('match_percentage')
            ->limit(5)
            ->get()
            ->map(fn ($score): array => [
                'name' => (string) ($score->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'score' => (float) $score->match_percentage,
            ])
            ->all();

        $aiRankBuckets = [
            '90%+' => 0,
            '70-89%' => 0,
            'Below 70%' => 0,
        ];

        $aiScores = AiScore::query()
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', (int) $request->user()->id)))
            ->pluck('match_percentage');

        foreach ($aiScores as $score) {
            $value = (float) $score;
            if ($value >= 90) {
                $aiRankBuckets['90%+']++;
            } elseif ($value >= 70) {
                $aiRankBuckets['70-89%']++;
            } else {
                $aiRankBuckets['Below 70%']++;
            }
        }

        $vacancyManagement = (clone $jobsQuery)
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'title', 'status', 'application_deadline'])
            ->map(fn ($job): array => [
                'id' => (int) $job->id,
                'title' => (string) $job->title,
                'status' => (string) $job->status,
                'applicants' => (int) $job->applications_count,
                'deadline' => optional($job->application_deadline)->format('Y-m-d H:i') ?? 'N/A',
            ])
            ->all();

        $smartApplicants = (clone $applicationsQuery)
            ->with(['applicant.user', 'applicant.skills', 'applicant.educations', 'applicant.experiences', 'job', 'aiScore'])
            ->latest('applied_at')
            ->limit(30)
            ->get()
            ->map(function ($application): array {
                $applicant = $application->applicant;
                $skills = $applicant?->skills?->pluck('name')->take(4)->values()->all() ?? [];
                $educationLevel = (string) ($applicant?->educations?->sortByDesc('created_at')->first()?->level ?? 'N/A');
                $experienceYears = (int) ($applicant?->experiences?->sum(function ($exp): int {
                    if (!$exp->start_date) {
                        return 0;
                    }

                    $endDate = $exp->end_date ?: now();

                    return max(0, Carbon::parse($exp->start_date)->diffInYears(Carbon::parse($endDate)));
                }) ?? 0);

                return [
                    'name' => (string) ($applicant?->user?->name ?? 'Unknown Candidate'),
                    'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                    'score' => (float) ($application->aiScore?->match_percentage ?? 0),
                    'status' => (string) ($application->status ?? 'pending'),
                    'skills' => $skills,
                    'education' => $educationLevel,
                    'experience_years' => $experienceYears,
                    'cv_available' => !empty($applicant?->cv_path),
                ];
            })
            ->all();

        $notifications = Notification::query()
            ->where('user_id', (int) $request->user()->id)
            ->latest()
            ->limit(8)
            ->get(['title', 'message', 'created_at'])
            ->map(fn ($item): array => [
                'title' => (string) $item->title,
                'message' => (string) $item->message,
                'at' => optional($item->created_at)->diffForHumans(),
            ])
            ->all();

        $unreadNotifications = Notification::query()
            ->where('user_id', (int) $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $statusRows = (clone $applicationsQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $statusMap = [
            'pending' => 0,
            'reviewed' => 0,
            'rejected' => 0,
            'shortlisted' => 0,
        ];

        foreach ($statusRows as $row) {
            $status = strtolower((string) $row->status);
            $count = (int) $row->total;
            if (in_array($status, ['submitted', 'pending', 'ai_processing'], true)) {
                $statusMap['pending'] += $count;
            } elseif (in_array($status, ['review', 'reviewed', 'under_review'], true)) {
                $statusMap['reviewed'] += $count;
            } elseif ($status === 'rejected') {
                $statusMap['rejected'] += $count;
            } elseif ($status === 'shortlisted') {
                $statusMap['shortlisted'] += $count;
            }
        }

        $activityLogs = AuditLog::query()
            ->when(!$isAdmin, fn ($query) => $query->where('user_id', (int) $request->user()->id))
            ->latest()
            ->limit(8)
            ->get(['action', 'created_at'])
            ->map(fn ($row): array => [
                'action' => (string) $row->action,
                'at' => optional($row->created_at)->diffForHumans(),
            ])
            ->all();

        $loginLogs = LoginHistory::query()
            ->when(!$isAdmin, fn ($query) => $query->where('user_id', (int) $request->user()->id))
            ->latest('logged_in_at')
            ->limit(8)
            ->get(['user_id', 'ip_address', 'logged_in_at'])
            ->map(fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'ip' => (string) ($row->ip_address ?? 'N/A'),
                'at' => optional($row->logged_in_at)->format('Y-m-d H:i'),
            ])
            ->all();

        return [
            'metrics' => $metrics,
            'weeklyGrowth' => $weeklyGrowth,
            'charts' => [
                'applicationsPerJob' => [
                    'labels' => $perJob->pluck('title')->toArray(),
                    'values' => $perJob->pluck('applications_count')->toArray(),
                ],
                'applicantTrends' => [
                    'labels' => $dailyLabels,
                    'values' => array_values($dailyMap),
                ],
                'qualificationDistribution' => [
                    'labels' => array_keys($qualificationMap),
                    'values' => array_values($qualificationMap),
                ],
                'genderDistribution' => [
                    'labels' => array_keys($genderMap),
                    'values' => array_values($genderMap),
                ],
                'departmentPerformance' => [
                    'labels' => array_map(fn (array $row): string => $row['department'], $departmentStats),
                    'values' => array_map(fn (array $row): int => $row['count'], $departmentStats),
                ],
                'aiRankingDistribution' => [
                    'labels' => array_keys($aiRankBuckets),
                    'values' => array_values($aiRankBuckets),
                ],
            ],
            'recentApplications' => $recentApplications,
            'upcomingInterviews' => $upcomingInterviews,
            'todayInterviews' => $todayInterviews,
            'interviewStatusOverview' => $interviewStatusMap,
            'topCandidates' => $topCandidates,
            'vacancyManagement' => $vacancyManagement,
            'smartApplicants' => $smartApplicants,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
            'statusOverview' => $statusMap,
            'activityLogs' => $activityLogs,
            'loginLogs' => $loginLogs,
            'departmentStats' => $departmentStats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildApplicantDashboardData(Request $request): array
    {
        $applicant = $this->resolveApplicant($request)->load(['skills', 'educations', 'experiences', 'certificates']);

        $applications = Application::query()
            ->with(['job', 'aiScore'])
            ->where('applicant_id', $applicant->id)
            ->latest('applied_at')
            ->get();

        $applicationIds = $applications->pluck('id')->all();
        $appliedJobIds = $applications->pluck('job_id')->all();

        $notifications = Notification::query()
            ->where('user_id', (int) $request->user()->id)
            ->latest()
            ->limit(8)
            ->get(['title', 'message', 'created_at'])
            ->map(fn ($item): array => [
                'title' => (string) $item->title,
                'message' => (string) $item->message,
                'at' => optional($item->created_at)->diffForHumans(),
            ])
            ->all();

        $upcomingInterviews = Interview::query()
            ->with(['application.job'])
            ->whereIn('application_id', $applicationIds)
            ->where('scheduled_at', '>=', now())
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn ($interview): array => [
                'id' => (int) $interview->id,
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('Y-m-d H:i'),
                'venue' => (string) ($interview->venue ?: $interview->meeting_link ?: 'TBA'),
                'mode' => (string) ($interview->mode ?? 'online'),
                'status' => (string) ($interview->status ?? 'scheduled'),
                'can_respond' => in_array((string) $interview->status, ['scheduled', 'invitation_sent'], true),
            ])
            ->all();

        $analysis = $this->cvAnalysisService->analyzeApplicant($applicant);

        $applicantSkills = collect((array) ($analysis['skills'] ?? []))
            ->map(fn (string $skill): string => strtolower(trim($skill)))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->unique()
            ->values()
            ->all();

        $experienceYears = (float) ($analysis['experience_years'] ?? 0.0);

        $hasBachelor = collect((array) ($analysis['education'] ?? []))
            ->contains(function ($education): bool {
                if (!is_array($education)) {
                    return false;
                }

                return str_contains(strtolower((string) ($education['level'] ?? '')), 'bachelor');
            });

        $openJobs = Job::query()
            ->where('status', 'published')
            ->where('application_deadline', '>=', now())
            ->limit(30)
            ->get();

        $buildRecommendationRow = function (Job $job, bool $alreadyApplied) use ($applicantSkills, $experienceYears, $hasBachelor): array {
                $required = collect((array) ($job->required_skills_json ?? []))
                    ->map(fn (string $skill): string => strtolower(trim($skill)))
                    ->filter(fn (string $skill): bool => $skill !== '')
                    ->values();

                $matchedSkills = $required
                    ->filter(fn (string $skill): bool => in_array($skill, $applicantSkills, true))
                    ->values();
                $missingSkills = $required
                    ->reject(fn (string $skill): bool => in_array($skill, $matchedSkills->all(), true))
                    ->values();

                $skillsScore = $required->count() > 0
                    ? (($matchedSkills->count() / $required->count()) * 100)
                    : 50.0;

                $targetExperience = max(1, (int) ($job->min_years_experience ?? 1));
                $experienceScore = min(100, ($experienceYears / $targetExperience) * 100);

                $needsBachelor = str_contains(strtolower((string) ($job->qualifications ?? '')), 'bachelor');
                $educationScore = $needsBachelor ? ($hasBachelor ? 100.0 : 25.0) : 70.0;

                $score = round(($skillsScore * 0.6) + ($experienceScore * 0.25) + ($educationScore * 0.15), 1);
                $recommendation = $this->recommendationLevelFromScore($score);

                $clearRecommendation = $this->buildJobRecommendationMessage(
                    $recommendation,
                    $matchedSkills->take(3)->values()->all(),
                    $missingSkills->take(3)->values()->all(),
                );

                if ($alreadyApplied) {
                    $clearRecommendation = 'You already applied for this vacancy. Keep tracking your status and use this as guidance for similar roles.';
                }

                return [
                    'id' => (int) $job->id,
                    'title' => (string) $job->title,
                    'score' => $score,
                    'location' => (string) ($job->location ?? 'N/A'),
                    'already_applied' => $alreadyApplied,
                    'recommendation' => $recommendation,
                    'matched_skills' => $matchedSkills->take(4)->values()->all(),
                    'missing_skills' => $missingSkills->take(4)->values()->all(),
                    'clear_recommendation' => $clearRecommendation,
                ];
            };

        $recommended = $openJobs
            ->filter(fn (Job $job): bool => !in_array((int) $job->id, $appliedJobIds, true))
            ->map(fn (Job $job): array => $buildRecommendationRow($job, false))
            ->sortByDesc('score')
            ->take(8)
            ->values()
            ->all();

        $recommendationsMeta = [
            'open_jobs_count' => (int) $openJobs->count(),
            'applied_jobs_count' => (int) count($appliedJobIds),
            'mode' => 'fresh',
        ];

        if (count($recommended) === 0 && $openJobs->count() > 0) {
            $recommended = $openJobs
                ->map(fn (Job $job): array => $buildRecommendationRow($job, in_array((int) $job->id, $appliedJobIds, true)))
                ->sortByDesc('score')
                ->take(8)
                ->values()
                ->all();

            $recommendationsMeta['mode'] = 'applied_fallback';
        }

        $completed = 0;
        $profileSignals = [
            !empty($applicant->phone),
            !empty($applicant->city),
            !empty($applicant->country),
            !empty($applicant->bio),
            !empty($applicant->cv_path),
            $applicant->skills()->exists(),
            $applicant->educations()->exists(),
            $applicant->experiences()->exists(),
        ];
        foreach ($profileSignals as $signal) {
            if ($signal) {
                $completed++;
            }
        }
        $profileCompletion = (int) round(($completed / max(count($profileSignals), 1)) * 100);

        $savedJobs = SavedJob::query()
            ->with('job')
            ->where('applicant_id', $applicant->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($saved): array => [
                'job_id' => (int) $saved->job_id,
                'title' => (string) ($saved->job?->title ?? 'Unknown Job'),
                'deadline' => optional($saved->job?->application_deadline)->format('Y-m-d'),
            ])
            ->all();

        $latestApplication = $applications->first();
        $aiFeedback = [];
        if ($latestApplication?->aiScore) {
            $missing = (array) ($latestApplication->aiScore->missing_skills ?? []);
            $aiFeedback = [
                'summary' => (string) ($latestApplication->aiScore->summary ?: 'Improve your CV by adding stronger achievements and role-specific skills.'),
                'missing_skills' => $missing,
            ];
        }

        $statusStats = [
            'pending' => $applications->whereIn('status', ['submitted', 'pending', 'review', 'under_review', 'ai_processing'])->count(),
            'reviewed' => $applications->whereIn('status', ['reviewed', 'review', 'under_review'])->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
            'shortlisted' => $applications->where('status', 'shortlisted')->count(),
        ];

        $interviewsAttended = Interview::query()
            ->whereIn('application_id', $applicationIds)
            ->where('status', 'completed')
            ->count();

        $successRate = $applications->count() > 0
            ? round(($statusStats['shortlisted'] / $applications->count()) * 100, 1)
            : 0.0;

        return [
            'myApplications' => $applications->take(10)->map(fn ($application): array => [
                'id' => (int) $application->id,
                'application_id' => (string) $application->application_id,
                'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                'status' => (string) $application->status,
                'applied_at' => optional($application->applied_at)->format('Y-m-d'),
                'offer_status' => (string) ($application->offer_status ?? ''),
                'offer_sent_at' => optional($application->offer_sent_at)->format('Y-m-d H:i'),
                'onboarding_status' => (string) ($application->onboarding_status ?? ''),
                'placement_status' => (string) ($application->placement_status ?? ''),
            ])->all(),
            'progress' => [
                'current_status' => (string) ($latestApplication?->status ?? 'none'),
                'steps' => $this->buildApplicationProgressSteps((string) ($latestApplication?->status ?? 'none')),
            ],
            'notifications' => $notifications,
            'recommendedJobs' => $recommended,
            'recommendationsMeta' => $recommendationsMeta,
            'profileCompletion' => $profileCompletion,
            'upcomingInterviews' => $upcomingInterviews,
            'savedJobs' => $savedJobs,
            'aiFeedback' => $aiFeedback,
            'accountStats' => [
                'total_applications' => $applications->count(),
                'interviews_attended' => $interviewsAttended,
                'success_rate' => $successRate,
            ],
            'statusOverview' => $statusStats,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildApplicationProgressSteps(string $status): array
    {
        $normalized = strtolower($status);
        $sequence = ['applied', 'under_review', 'shortlisted', 'interview_pending', 'offer_sent', 'onboarding', 'placement_closed'];
        $index = match (true) {
            in_array($normalized, ['submitted', 'pending', 'ai_processing'], true) => 0,
            in_array($normalized, ['review', 'reviewed', 'under_review'], true) => 1,
            $normalized === 'shortlisted' => 2,
            in_array($normalized, ['interview', 'interview_pending', 'interview_scheduled'], true) => 3,
            $normalized === 'offer_sent' => 4,
            in_array($normalized, ['hired', 'onboarding_completed'], true) => 5,
            $normalized === 'placed' => 6,
            default => -1,
        };

        return collect($sequence)->map(function (string $step, int $stepIndex) use ($index): array {
            return [
                'label' => ucwords(str_replace('_', ' ', $step)),
                'done' => $stepIndex <= $index,
            ];
        })->all();
    }

    private function recommendationLevelFromScore(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Strong Fit',
            $score >= 50 => 'Potential Fit',
            default => 'Low Fit',
        };
    }

    /**
     * @param array<int, string> $matchedSkills
     * @param array<int, string> $missingSkills
     */
    private function buildJobRecommendationMessage(string $recommendation, array $matchedSkills, array $missingSkills): string
    {
        if ($recommendation === 'Strong Fit') {
            if (count($missingSkills) === 0) {
                return 'You are a strong match for this job. Apply now and highlight your proven skills.';
            }

            return 'You are close to an excellent fit. Apply now and address missing skills: ' . implode(', ', $missingSkills) . '.';
        }

        if ($recommendation === 'Potential Fit') {
            if (count($missingSkills) === 0) {
                return 'You can apply, but improve your CV achievements to increase shortlist chances.';
            }

            return 'You have a moderate fit. Improve these skills before applying: ' . implode(', ', $missingSkills) . '.';
        }

        if (count($matchedSkills) > 0) {
            return 'Not recommended for now. Build more role-specific skills beyond: ' . implode(', ', $matchedSkills) . '.';
        }

        return 'Not recommended for now. Update your profile skills and CV to receive better matches.';
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

    private function ensureHrApplicationAccess(Request $request, Application $application): void
    {
        $application->loadMissing('job');

        $role = strtolower((string) $request->user()->role);
        $isAdmin = in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);

        if ($isAdmin) {
            return;
        }

        abort_if((int) ($application->job?->hr_officer_id ?? 0) !== (int) $request->user()->id, 403, 'You are not authorized for this application.');
    }

    private function ensureHrInterviewAccess(Request $request, Interview $interview): void
    {
        $interview->loadMissing('application.job');
        abort_unless($interview->application instanceof Application, 404, 'Interview application not found.');

        $this->ensureHrApplicationAccess($request, $interview->application);
    }
}
