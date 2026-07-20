<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessApplicationAiScore;
use App\Mail\InterviewScheduledMail;
use App\Models\AiScore;
use App\Models\Applicant;
use App\Models\ApplicantLanguage;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Education;
use App\Models\GeneratedDocument;
use App\Models\Interview;
use App\Models\Job;
use App\Models\LoginHistory;
use App\Models\Notification;
use App\Models\SavedJob;
use App\Models\Skill;
use App\Services\Ai\ChatbotAssistantService;
use App\Services\Ai\CvAnalysisService;
use App\Services\Ai\RecruitmentScoringService;
use App\Services\AuditLogService;
use App\Services\CandidateCommunicationService;
use App\Services\DocumentVerificationService;
use App\Services\NotificationService;
use App\Services\OutboundChannelService;
use App\Services\ResumeTextExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PortalController extends Controller
{
    private const FINAL_DECISION_STATUSES = ['hired', 'placed', 'offer_declined', 'rejected'];

    public function __construct(
        private readonly RecruitmentScoringService $recruitmentScoringService,
        private readonly CvAnalysisService $cvAnalysisService,
        private readonly NotificationService $notificationService,
        private readonly CandidateCommunicationService $candidateCommunicationService,
        private readonly OutboundChannelService $outboundChannelService,
        private readonly AuditLogService $auditLogService,
        private readonly ResumeTextExtractor $resumeTextExtractor,
        private readonly DocumentVerificationService $documentVerificationService,
        private readonly ChatbotAssistantService $chatbotAssistantService,
    ) {
    }

    public function hrDashboard(Request $request): View
    {
        return view('hr.dashboard', [
            'pageTitle' => __('messages.hr_dashboard'),
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
            'pageTitle' => __('messages.interviews'),
            'pageHeading' => 'Interviews',
            'activeNav' => 'interviews',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrAnalyticsReports(Request $request): View
    {
        return view('hr.analytics-reports', [
            'pageTitle' => __('messages.analytics_reports'),
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
            'pageTitle' => __('messages.notifications'),
            'pageHeading' => 'Notifications',
            'activeNav' => 'notifications',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function hrDepartments(Request $request): View
    {
        return view('hr.departments', [
            'pageTitle' => __('messages.departments'),
            'pageHeading' => 'Departments',
            'activeNav' => 'departments',
            'dashboard' => $this->buildHrDashboardData($request),
        ]);
    }

    public function applicantDashboard(Request $request): View
    {
        return view('applicant.dashboard', [
            'pageTitle' => __('messages.my_dashboard'),
            'pageHeading' => 'Applicant Dashboard',
            'activeNav' => 'dashboard',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantApplications(Request $request): View
    {
        return view('applicant.applications', [
            'pageTitle' => __('messages.my_applications'),
            'pageHeading' => 'My Applications',
            'activeNav' => 'applicant.applications',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantRecommendations(Request $request): View
    {
        return view('applicant.recommendations', [
            'pageTitle' => __('messages.ai_recommendations'),
            'pageHeading' => 'AI Recommendations',
            'activeNav' => 'applicant.recommendations',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantInterviews(Request $request): View
    {
        return view('applicant.interviews', [
            'pageTitle' => __('messages.interviews'),
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
            'pageTitle' => __('messages.notifications'),
            'pageHeading' => 'Notifications',
            'activeNav' => 'applicant.notifications',
            'dashboard' => $this->buildApplicantDashboardData($request),
        ]);
    }

    public function applicantDownloads(Request $request): View
    {
        return view('applicant.downloads', [
            'pageTitle' => __('messages.download_center'),
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
        $applicant = $this->resolveApplicant($request)->load('user');
        abort_if((int) $application->applicant_id !== (int) $applicant->id, 403);

        $application->load('job');
        $applicantName = (string) ($applicant->user->name ?? 'Applicant');
        $jobTitle = $application->job?->title;

        $issued = $this->documentVerificationService->issue(
            GeneratedDocument::TYPE_APPLICATION_SLIP,
            (int) $applicant->user_id,
            $applicantName,
            $jobTitle,
            (int) $application->id
        );

        $pdf = Pdf::loadView('exports.application-slip-pdf', [
            'application' => $application,
            'document' => $issued['document'],
            'qrDataUri' => $issued['qrDataUri'],
            'verifyUrl' => $issued['verifyUrl'],
            'applicantName' => $applicantName,
            'jobTitle' => $jobTitle,
        ]);

        return $pdf->download('application-slip-' . $application->application_id . '.pdf');
    }

    public function downloadInterviewInvitation(Request $request, Interview $interview): Response
    {
        $interview->load(['application.job', 'interviewer']);
        $applicant = $this->resolveApplicant($request)->load('user');
        abort_if((int) ($interview->application?->applicant_id ?? 0) !== (int) $applicant->id, 403);

        $applicantName = (string) ($applicant->user->name ?? 'Applicant');
        $jobTitle = $interview->application?->job?->title;

        $issued = $this->documentVerificationService->issue(
            GeneratedDocument::TYPE_INTERVIEW_LETTER,
            (int) $applicant->user_id,
            $applicantName,
            $jobTitle,
            (int) ($interview->application_id ?? 0) ?: null,
            (int) $interview->id
        );

        $pdf = Pdf::loadView('exports.interview-letter-pdf', [
            'document' => $issued['document'],
            'qrDataUri' => $issued['qrDataUri'],
            'verifyUrl' => $issued['verifyUrl'],
            'applicantName' => $applicantName,
            'jobTitle' => $jobTitle,
            'scheduledAt' => optional($interview->scheduled_at)->format('Y-m-d H:i') ?? 'N/A',
            'mode' => strtoupper((string) $interview->mode),
            'venue' => (string) ($interview->venue ?: ''),
            'meetingLink' => (string) ($interview->meeting_link ?: 'N/A'),
            'recruiterContact' => (string) ($interview->interviewer?->email ?? $interview->interviewer?->name ?? 'HR Team'),
        ]);

        return $pdf->download('interview-letter-' . $interview->id . '.pdf');
    }

    public function downloadOfferLetter(Request $request, Application $application): Response
    {
        $applicant = $this->resolveApplicant($request)->load('user');
        abort_if((int) $application->applicant_id !== (int) $applicant->id, 403);

        $eligible = in_array((string) $application->status, ['shortlisted', 'hired', 'offer_sent'], true);
        abort_unless($eligible, 403, 'Offer letter is not available for this application yet.');

        $application->load('job');
        $applicantName = (string) ($applicant->user->name ?? 'Applicant');
        $jobTitle = $application->job?->title;

        $issued = $this->documentVerificationService->issue(
            GeneratedDocument::TYPE_OFFER_LETTER,
            (int) $applicant->user_id,
            $applicantName,
            $jobTitle,
            (int) $application->id
        );

        $pdf = Pdf::loadView('exports.offer-letter-pdf', [
            'application' => $application,
            'applicant' => $applicant,
            'document' => $issued['document'],
            'qrDataUri' => $issued['qrDataUri'],
            'verifyUrl' => $issued['verifyUrl'],
            'applicantName' => $applicantName,
            'jobTitle' => $jobTitle,
        ]);

        return $pdf->download('offer-letter-' . $application->application_id . '.pdf');
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
            'pageTitle' => __('messages.applicant_profile'),
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
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
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
            'gpa' => $validated['gpa'] ?? null,
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
            'pageTitle' => __('messages.browse_jobs'),
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
            'pageTitle' => __('messages.department_named_vacancies', ['department' => $department->name]),
            'pageHeading' => __('messages.department_named_vacancies', ['department' => $department->name]),
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
            'pageTitle' => __('messages.job_requirements'),
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
            'pageTitle' => __('messages.apply_job_title'),
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

    public function applicantChatbotMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $question = trim($validated['question']);
        if ($question === '') {
            return response()->json(['reply' => 'Please type a question and I\'ll help you out.'], 422);
        }

        if ((string) config('services.gemini.api_key') === '') {
            return response()->json(['reply' => 'AI support is unavailable right now. Please try again later.'], 503);
        }

        $applicant = $this->resolveApplicant($request)->load([
            'user',
            'applications.job',
            'applications.aiScore',
            'applications.interviews',
            'savedJobs.job',
        ]);

        $historyKey = 'chatbot_history_applicant_' . $request->user()->id;
        $history = (array) session($historyKey, []);

        try {
            $context = $this->buildApplicantChatContext($applicant);
            $reply = $this->chatbotAssistantService->reply($context, $question, $history);
        } catch (Throwable $exception) {
            Log::warning('Applicant chatbot request failed unexpectedly.', ['exception' => $exception->getMessage()]);
            $reply = null;
        }

        if ($reply === null) {
            return response()->json(['reply' => 'AI support could not generate an answer right now. Please try again.'], 500);
        }

        $history[] = ['role' => 'user', 'text' => $question];
        $history[] = ['role' => 'assistant', 'text' => $reply];
        session([$historyKey => array_slice($history, -8)]);

        return response()->json(['reply' => $reply]);
    }

    public function hrChatbotMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $question = trim($validated['question']);
        if ($question === '') {
            return response()->json(['reply' => 'Please type a question and I\'ll help you out.'], 422);
        }

        if ((string) config('services.gemini.api_key') === '') {
            return response()->json(['reply' => 'AI support is unavailable right now. Please try again later.'], 503);
        }

        $historyKey = 'chatbot_history_hr_' . $request->user()->id;
        $history = (array) session($historyKey, []);

        try {
            $context = $this->buildRecruiterChatContext($request);
            $reply = $this->chatbotAssistantService->reply($context, $question, $history);
        } catch (Throwable $exception) {
            Log::warning('HR chatbot request failed unexpectedly.', ['exception' => $exception->getMessage()]);
            $reply = null;
        }

        if ($reply === null) {
            return response()->json(['reply' => 'AI support could not generate an answer right now. Please try again.'], 500);
        }

        $history[] = ['role' => 'user', 'text' => $question];
        $history[] = ['role' => 'assistant', 'text' => $reply];
        session([$historyKey => array_slice($history, -8)]);

        return response()->json(['reply' => $reply]);
    }

    private function buildApplicantChatContext(Applicant $applicant): string
    {
        $lines = [];
        $lines[] = 'Applicant name: ' . ($applicant->user->name ?? 'Unknown');
        $lines[] = 'Applicant email: ' . ($applicant->user->email ?? 'Unknown');
        $lines[] = 'GPA: ' . ($applicant->gpa ?? 'not provided');

        $lines[] = 'Applications:';
        foreach ($applicant->applications as $application) {
            $score = $application->aiScore;
            $lines[] = sprintf(
                '- Job "%s" | status: %s | applied: %s%s',
                $application->job?->title ?? 'Unknown',
                $application->status,
                optional($application->applied_at)->format('Y-m-d') ?? 'N/A',
                $score ? sprintf(
                    ' | AI match: %.1f%% (%s), strengths: %s, weaknesses: %s',
                    (float) $score->match_percentage,
                    $score->recommendation_level,
                    implode(', ', (array) ($score->strengths ?? [])) ?: 'none listed',
                    implode(', ', (array) ($score->weaknesses ?? [])) ?: 'none listed'
                ) : ' | AI score: not yet available'
            );
        }
        if ($applicant->applications->isEmpty()) {
            $lines[] = '- No applications submitted yet.';
        }

        $lines[] = 'Upcoming interviews:';
        $hasInterview = false;
        foreach ($applicant->applications as $application) {
            foreach ($application->interviews as $interview) {
                if (!in_array((string) $interview->status, ['scheduled', 'confirmed'], true)) {
                    continue;
                }
                $hasInterview = true;
                $lines[] = sprintf(
                    '- %s on %s (%s) at %s',
                    $application->job?->title ?? 'Unknown role',
                    optional($interview->scheduled_at)->format('Y-m-d H:i') ?? 'N/A',
                    $interview->mode,
                    $interview->venue ?: ($interview->meeting_link ?: 'TBA')
                );
            }
        }
        if (!$hasInterview) {
            $lines[] = '- No upcoming interviews scheduled.';
        }

        $savedJobTitles = $applicant->savedJobs->map(fn ($saved) => $saved->job?->title)->filter()->values();
        $lines[] = 'Saved jobs: ' . ($savedJobTitles->isNotEmpty() ? $savedJobTitles->implode(', ') : 'none');

        $appliedJobIds = $applicant->applications->pluck('job_id')->filter()->values();
        $openJobs = Job::query()
            ->with('department')
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')->orWhere('application_deadline', '>=', now());
            })
            ->latest()
            ->limit(15)
            ->get();

        $lines[] = 'Currently open job postings (status=published, deadline not passed):';
        foreach ($openJobs as $openJob) {
            $lines[] = sprintf(
                '- "%s" | department: %s | positions: %d | deadline: %s | %s',
                $openJob->title,
                $openJob->department?->name ?? 'N/A',
                $openJob->positions,
                optional($openJob->application_deadline)->format('Y-m-d') ?? 'no deadline',
                $appliedJobIds->contains($openJob->id) ? 'already applied' : 'not yet applied'
            );
        }
        if ($openJobs->isEmpty()) {
            $lines[] = '- No open job postings right now.';
        }

        $cvText = trim((string) $applicant->cv_text);
        if ($cvText !== '') {
            $lines[] = 'Resume text (excerpt): ' . mb_substr($cvText, 0, 2500);
        }

        return implode("\n", $lines);
    }

    private function buildRecruiterChatContext(Request $request): string
    {
        $role = strtolower((string) $request->user()->role);
        $isAdmin = in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);

        $jobsQuery = Job::query()
            ->when(!$isAdmin, fn ($query) => $query->where('hr_officer_id', (int) $request->user()->id));

        $jobs = $jobsQuery->latest()->limit(20)->get(['id', 'title', 'status', 'positions', 'application_deadline']);

        $lines = [];
        $lines[] = 'Recruiter: ' . ($request->user()->name ?? 'Unknown');
        $lines[] = 'Job postings:';
        foreach ($jobs as $job) {
            $applicationCount = Application::query()->where('job_id', $job->id)->count();
            $lines[] = sprintf(
                '- "%s" | status: %s | positions: %d | deadline: %s | applications: %d',
                $job->title,
                $job->status,
                $job->positions,
                optional($job->application_deadline)->format('Y-m-d') ?? 'N/A',
                $applicationCount
            );
        }
        if ($jobs->isEmpty()) {
            $lines[] = '- No job postings found.';
        }

        $jobIds = $jobsQuery->pluck('id');
        $statusCounts = Application::query()
            ->whereIn('job_id', $jobIds)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $lines[] = 'Application pipeline (by status): ' . ($statusCounts->isNotEmpty()
            ? collect($statusCounts)->map(fn ($count, $status) => "{$status}: {$count}")->implode(', ')
            : 'no applications yet');

        $scoreStats = AiScore::query()
            ->whereHas('application', fn ($query) => $query->whereIn('job_id', $jobIds))
            ->selectRaw('recommendation_level, COUNT(*) as total, AVG(match_percentage) as avg_score')
            ->groupBy('recommendation_level')
            ->get();

        $lines[] = 'AI score distribution: ' . ($scoreStats->isNotEmpty()
            ? $scoreStats->map(fn ($row) => sprintf('%s: %d (avg %.1f%%)', $row->recommendation_level, $row->total, (float) $row->avg_score))->implode(', ')
            : 'no AI scores yet');

        return implode("\n", $lines);
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

        $emailSent = false;
        $applicantEmail = (string) ($application->applicant?->user?->email ?? '');
        if ($applicantEmail !== '') {
            $interview->load(['application.job', 'application.applicant.user', 'interviewer']);

            try {
                Mail::to($applicantEmail)->send(new InterviewScheduledMail($interview));
                $emailSent = true;
            } catch (Throwable $exception) {
                Log::warning('Interview invitation email failed to send.', [
                    'interview_id' => $interview->id,
                    'exception' => $exception->getMessage(),
                ]);
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

        return back()->with('success', 'Interview scheduled successfully.' . ($emailSent ? ' Invitation email sent.' : ' Email invitation could not be sent.'));
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
            ->get(['id', 'title', 'positions']);

        $selectedJobId = (int) $request->query('job_id', 0);
        if ($selectedJobId === 0 && $jobs->isNotEmpty()) {
            $selectedJobId = (int) $jobs->first()->id;
        }

        $selectedJobPositions = (int) ($jobs->firstWhere('id', $selectedJobId)?->positions ?? 0);

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

        $query
            ->orderByDesc(
                AiScore::query()
                    ->select('match_percentage')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->orderByDesc(
                AiScore::query()
                    ->select('fit_score')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->orderBy('applications.applied_at')
            ->orderBy('applications.id');

        $perPage = 15;
        $topN = (int) $request->query('top_n', 0);

        if ($topN > 0) {
            // A recruiter-chosen cap (e.g. "show only the top 50") must limit the whole
            // ranked pool before pagination, not just the current page — Eloquent's own
            // paginate() always overrides any prior ->limit() with its own page-sized one,
            // so the only way to cap the total pool is to fetch it and paginate manually.
            $topApplications = $query->take($topN)->get();
            $page = (int) $request->query('page', 1);

            $applications = new LengthAwarePaginator(
                $topApplications->forPage($page, $perPage)->values(),
                $topApplications->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $applications = $query->paginate($perPage)->withQueryString();
        }

        return view('hr.candidate-ranking', [
            'pageTitle' => __('messages.applicants'),
            'pageHeading' => 'Applicant',
            'activeNav' => 'hr.ranking',
            'jobs' => $jobs,
            'selectedJobId' => $selectedJobId,
            'selectedJobPositions' => $selectedJobPositions,
            'topN' => $topN,
            'applications' => $applications,
        ]);
    }

    public function hrAiResults(Request $request, Application $application): View
    {
        $this->ensureHrApplicationAccess($request, $application);

        $application->load(['applicant.user', 'applicant.skills', 'applicant.educations', 'applicant.experiences', 'applicant.projects', 'applicant.achievements', 'aiScore', 'job']);

        return view('hr.ai-results', [
            'pageTitle' => __('messages.ai_results'),
            'pageHeading' => 'AI Results',
            'activeNav' => 'hr.ai-results',
            'application' => $application,
        ]);
    }

    public function hrRescanApplication(Request $request, Application $application): RedirectResponse
    {
        $this->ensureHrApplicationAccess($request, $application);

        if (in_array((string) $application->status, self::FINAL_DECISION_STATUSES, true)) {
            return back()->with('error', 'A final decision has already been made on this application, so it can no longer be re-scanned.');
        }

        ProcessApplicationAiScore::dispatch((int) $application->id);

        return back()->with('success', 'AI re-scan complete. Results updated below.');
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
            'pageTitle' => __('messages.job_management'),
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
            'pageTitle' => __('messages.create_vacancy'),
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
            'pageTitle' => __('messages.edit_vacancy'),
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
        $isAdmin = $this->isHrAdminScope($request);
        $userId = (int) $request->user()->id;

        $departmentStats = $this->buildHrDepartmentStats($isAdmin, $userId);

        return [
            'metrics' => $this->buildHrMetrics($isAdmin, $userId),
            'weeklyGrowth' => $this->buildHrWeeklyGrowth($isAdmin, $userId),
            'charts' => $this->buildHrChartData($isAdmin, $userId, $departmentStats),
            'recentApplications' => $this->buildHrRecentApplications($isAdmin, $userId),
            'upcomingInterviews' => $this->buildHrUpcomingInterviews($isAdmin, $userId),
            'todayInterviews' => $this->buildHrTodayInterviews($isAdmin, $userId),
            'interviewStatusOverview' => $this->buildHrInterviewStatusMap($isAdmin, $userId),
            'topCandidates' => $this->buildHrTopCandidates($isAdmin, $userId),
            'vacancyManagement' => $this->buildHrVacancyManagement($isAdmin, $userId),
            'smartApplicants' => $this->buildHrSmartApplicants($isAdmin, $userId),
            'notifications' => $this->buildHrNotifications($userId),
            'unreadNotifications' => $this->countHrUnreadNotifications($userId),
            'statusOverview' => $this->buildHrStatusOverview($isAdmin, $userId),
            'activityLogs' => $this->buildHrActivityLogs($isAdmin, $userId),
            'loginLogs' => $this->buildHrLoginLogs($isAdmin, $userId),
            'departmentStats' => $departmentStats,
        ];
    }

    private function isHrAdminScope(Request $request): bool
    {
        $role = strtolower((string) $request->user()->role);

        return in_array($role, ['admin', 'hr_manager', 'hr-manager'], true);
    }

    private function hrScopedJobsQuery(bool $isAdmin, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return Job::query()->when(!$isAdmin, fn ($query) => $query->where('hr_officer_id', $userId));
    }

    private function hrScopedApplicationsQuery(bool $isAdmin, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return Application::query()->whereHas(
            'job',
            fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId))
        );
    }

    /**
     * @return array{Total Applicants: int, Open Vacancies: int, Shortlisted: int, Pending Applications: int, AI Completed: int, AI Total: int}
     */
    private function buildHrMetrics(bool $isAdmin, int $userId): array
    {
        $applicationsQuery = $this->hrScopedApplicationsQuery($isAdmin, $userId);
        $totalApplications = (clone $applicationsQuery)->count();

        return [
            'Total Applicants' => (clone $applicationsQuery)->distinct('applicant_id')->count('applicant_id'),
            'Open Vacancies' => $this->hrScopedJobsQuery($isAdmin, $userId)->where('status', 'published')->where('application_deadline', '>=', now())->count(),
            'Shortlisted' => (clone $applicationsQuery)->where(function ($query): void {
                $query->where('status', 'shortlisted')
                    ->orWhereHas('aiScore', fn ($scoreQuery) => $scoreQuery->whereIn('recommendation_level', ['Shortlisted', 'Highly Qualified']));
            })->count(),
            'Pending Applications' => (clone $applicationsQuery)->whereIn('status', ['submitted', 'pending', 'review', 'under_review', 'ai_processing'])->count(),
            'AI Completed' => (clone $applicationsQuery)->whereHas('aiScore')->count(),
            'AI Total' => $totalApplications,
        ];
    }

    private function buildHrWeeklyGrowth(bool $isAdmin, int $userId): float
    {
        $applicationsQuery = $this->hrScopedApplicationsQuery($isAdmin, $userId);

        $currentWeek = (clone $applicationsQuery)->where('applied_at', '>=', now()->startOfWeek())->count();
        $previousWeek = (clone $applicationsQuery)
            ->whereBetween('applied_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();

        return $previousWeek > 0
            ? round((($currentWeek - $previousWeek) / $previousWeek) * 100, 1)
            : ($currentWeek > 0 ? 100.0 : 0.0);
    }

    /**
     * @param array<int, array{department: string, count: int}> $departmentStats
     * @return array<string, array{labels: array<int, mixed>, values: array<int, mixed>}>
     */
    private function buildHrChartData(bool $isAdmin, int $userId, array $departmentStats): array
    {
        $perJob = $this->hrScopedJobsQuery($isAdmin, $userId)
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

        $trendRows = $this->hrScopedApplicationsQuery($isAdmin, $userId)
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

        $qualificationRows = Education::query()
            ->whereHas('applicant.applications.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
            ->get(['level']);

        $qualificationMap = $this->buildHrQualificationDistribution($qualificationRows);

        $genderRows = Applicant::query()
            ->whereHas('applications.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
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

        $aiRankBuckets = $this->buildHrAiRankBuckets($isAdmin, $userId);

        return [
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
        ];
    }

    /**
     * @param iterable<int, object{level: mixed}> $rows
     * @return array{Degree: int, Diploma: int, Masters: int, Other: int}
     */
    private function buildHrQualificationDistribution(iterable $rows): array
    {
        $qualificationMap = [
            'Degree' => 0,
            'Diploma' => 0,
            'Masters' => 0,
            'Other' => 0,
        ];

        foreach ($rows as $row) {
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

        return $qualificationMap;
    }

    /**
     * @return array{90%+: int, 70-89%: int, Below 70%: int}
     */
    private function buildHrAiRankBuckets(bool $isAdmin, int $userId): array
    {
        $aiRankBuckets = [
            '90%+' => 0,
            '70-89%' => 0,
            'Below 70%' => 0,
        ];

        $aiScores = AiScore::query()
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
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

        return $aiRankBuckets;
    }

    /**
     * @return array<int, array{department: string, count: int}>
     */
    private function buildHrDepartmentStats(bool $isAdmin, int $userId): array
    {
        $departmentRows = Job::query()
            ->selectRaw('department_id, COUNT(applications.id) as applicants_count')
            ->join('applications', 'applications.job_id', '=', 'jobs.id')
            ->when(!$isAdmin, fn ($query) => $query->where('jobs.hr_officer_id', $userId))
            ->groupBy('department_id')
            ->get();

        return $departmentRows->map(function ($row): array {
            $department = Department::query()->find($row->department_id);

            return [
                'department' => $department?->name ?? 'Unassigned',
                'count' => (int) $row->applicants_count,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array{candidate: string, job: string, at: ?string}>
     */
    private function buildHrRecentApplications(bool $isAdmin, int $userId): array
    {
        return $this->hrScopedApplicationsQuery($isAdmin, $userId)
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
    }

    /**
     * @return array<int, array{id: int, candidate: string, job: string, scheduled_at: ?string, mode: string, venue: string, meeting_link: string, status: string}>
     */
    private function buildHrUpcomingInterviews(bool $isAdmin, int $userId): array
    {
        return Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
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
    }

    /**
     * @return array<int, array{id: int, candidate: string, job: string, scheduled_at: ?string, status: string}>
     */
    private function buildHrTodayInterviews(bool $isAdmin, int $userId): array
    {
        return Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
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
    }

    /**
     * @return array{scheduled: int, invitation_sent: int, confirmed: int, completed: int, cancelled: int}
     */
    private function buildHrInterviewStatusMap(bool $isAdmin, int $userId): array
    {
        $interviewStatusMap = [
            'scheduled' => 0,
            'invitation_sent' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        $interviewStatusRows = Interview::query()
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        foreach ($interviewStatusRows as $row) {
            $status = strtolower((string) $row->status);
            $count = (int) $row->total;

            if (array_key_exists($status, $interviewStatusMap)) {
                $interviewStatusMap[$status] += $count;
            } elseif ($status === 'sent') {
                $interviewStatusMap['invitation_sent'] += $count;
            }
        }

        return $interviewStatusMap;
    }

    /**
     * @return array<int, array{name: string, score: float}>
     */
    private function buildHrTopCandidates(bool $isAdmin, int $userId): array
    {
        return AiScore::query()
            ->with(['application.applicant.user'])
            ->whereHas('application.job', fn ($jobQuery) => $jobQuery->when(!$isAdmin, fn ($nested) => $nested->where('hr_officer_id', $userId)))
            ->orderByDesc('match_percentage')
            ->limit(5)
            ->get()
            ->map(fn ($score): array => [
                'name' => (string) ($score->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'score' => (float) $score->match_percentage,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, status: string, applicants: int, deadline: string}>
     */
    private function buildHrVacancyManagement(bool $isAdmin, int $userId): array
    {
        return $this->hrScopedJobsQuery($isAdmin, $userId)
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
    }

    /**
     * @return array<int, array{name: string, job: string, score: float, status: string, skills: array<int, string>, education: string, experience_years: int, cv_available: bool}>
     */
    private function buildHrSmartApplicants(bool $isAdmin, int $userId): array
    {
        return $this->hrScopedApplicationsQuery($isAdmin, $userId)
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
    }

    /**
     * @return array<int, array{id: int, title: string, message: string, read: bool, at: ?string}>
     */
    private function buildHrNotifications(int $userId): array
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(8)
            ->get(['id', 'title', 'message', 'read_at', 'created_at'])
            ->map(fn ($item): array => [
                'id' => (int) $item->id,
                'title' => (string) $item->title,
                'message' => (string) $item->message,
                'read' => $item->read_at !== null,
                'at' => optional($item->created_at)->diffForHumans(),
            ])
            ->all();
    }

    private function countHrUnreadNotifications(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array{pending: int, reviewed: int, rejected: int, shortlisted: int}
     */
    private function buildHrStatusOverview(bool $isAdmin, int $userId): array
    {
        $statusMap = [
            'pending' => 0,
            'reviewed' => 0,
            'rejected' => 0,
            'shortlisted' => 0,
        ];

        $statusRows = $this->hrScopedApplicationsQuery($isAdmin, $userId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

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

        return $statusMap;
    }

    /**
     * @return array<int, array{action: string, at: ?string}>
     */
    private function buildHrActivityLogs(bool $isAdmin, int $userId): array
    {
        return AuditLog::query()
            ->when(!$isAdmin, fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->limit(8)
            ->get(['action', 'created_at'])
            ->map(fn ($row): array => [
                'action' => (string) $row->action,
                'at' => optional($row->created_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{user_id: int, ip: string, at: ?string}>
     */
    private function buildHrLoginLogs(bool $isAdmin, int $userId): array
    {
        return LoginHistory::query()
            ->when(!$isAdmin, fn ($query) => $query->where('user_id', $userId))
            ->latest('logged_in_at')
            ->limit(8)
            ->get(['user_id', 'ip_address', 'logged_in_at'])
            ->map(fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'ip' => (string) ($row->ip_address ?? 'N/A'),
                'at' => optional($row->logged_in_at)->format('Y-m-d H:i'),
            ])
            ->all();
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
        $latestApplication = $applications->first();

        [$recommended, $recommendationsMeta] = $this->buildApplicantJobRecommendations($applicant, $appliedJobIds);
        $statusStats = $this->buildApplicantStatusStats($applications);

        return [
            'myApplications' => $this->buildApplicantApplicationsList($applications),
            'progress' => [
                'current_status' => (string) ($latestApplication?->status ?? 'none'),
                'steps' => $this->buildApplicationProgressSteps((string) ($latestApplication?->status ?? 'none')),
            ],
            'notifications' => $this->buildApplicantNotifications((int) $request->user()->id),
            'recommendedJobs' => $recommended,
            'recommendationsMeta' => $recommendationsMeta,
            'profileCompletion' => $this->calculateApplicantProfileCompletion($applicant),
            'upcomingInterviews' => $this->buildApplicantUpcomingInterviews($applicationIds),
            'savedJobs' => $this->buildApplicantSavedJobs((int) $applicant->id),
            'aiFeedback' => $this->buildApplicantAiFeedback($latestApplication),
            'accountStats' => [
                'total_applications' => $applications->count(),
                'interviews_attended' => $this->countApplicantInterviewsAttended($applicationIds),
                'success_rate' => $applications->count() > 0
                    ? round(($statusStats['shortlisted'] / $applications->count()) * 100, 1)
                    : 0.0,
            ],
            'statusOverview' => $statusStats,
        ];
    }

    /**
     * @return array<int, array{id: int, title: string, message: string, read: bool, at: ?string}>
     */
    private function buildApplicantNotifications(int $userId): array
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(8)
            ->get(['id', 'title', 'message', 'read_at', 'created_at'])
            ->map(fn ($item): array => [
                'id' => (int) $item->id,
                'title' => (string) $item->title,
                'message' => (string) $item->message,
                'read' => $item->read_at !== null,
                'at' => optional($item->created_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @param array<int, int> $applicationIds
     * @return array<int, array{id: int, job: string, scheduled_at: ?string, venue: string, mode: string, status: string, can_respond: bool}>
     */
    private function buildApplicantUpcomingInterviews(array $applicationIds): array
    {
        return Interview::query()
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
    }

    /**
     * @param array<int, int> $appliedJobIds
     * @return array{0: array<int, array<string, mixed>>, 1: array{open_jobs_count: int, applied_jobs_count: int, mode: string}}
     */
    private function buildApplicantJobRecommendations(Applicant $applicant, array $appliedJobIds): array
    {
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

        return [$recommended, $recommendationsMeta];
    }

    private function calculateApplicantProfileCompletion(Applicant $applicant): int
    {
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

        return (int) round(($completed / max(count($profileSignals), 1)) * 100);
    }

    /**
     * @return array<int, array{job_id: int, title: string, deadline: ?string}>
     */
    private function buildApplicantSavedJobs(int $applicantId): array
    {
        return SavedJob::query()
            ->with('job')
            ->where('applicant_id', $applicantId)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($saved): array => [
                'job_id' => (int) $saved->job_id,
                'title' => (string) ($saved->job?->title ?? 'Unknown Job'),
                'deadline' => optional($saved->job?->application_deadline)->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * @return array{}|array{summary: string, missing_skills: array<int, mixed>}
     */
    private function buildApplicantAiFeedback(?Application $latestApplication): array
    {
        if (!$latestApplication?->aiScore) {
            return [];
        }

        return [
            'summary' => (string) ($latestApplication->aiScore->summary ?: 'Improve your CV by adding stronger achievements and role-specific skills.'),
            'missing_skills' => (array) ($latestApplication->aiScore->missing_skills ?? []),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, Application> $applications
     * @return array{pending: int, reviewed: int, rejected: int, shortlisted: int}
     */
    private function buildApplicantStatusStats(\Illuminate\Support\Collection $applications): array
    {
        return [
            'pending' => $applications->whereIn('status', ['submitted', 'pending', 'review', 'under_review', 'ai_processing'])->count(),
            'reviewed' => $applications->whereIn('status', ['reviewed', 'review', 'under_review'])->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
            'shortlisted' => $applications->where('status', 'shortlisted')->count(),
        ];
    }

    /**
     * @param array<int, int> $applicationIds
     */
    private function countApplicantInterviewsAttended(array $applicationIds): int
    {
        return Interview::query()
            ->whereIn('application_id', $applicationIds)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * @param \Illuminate\Support\Collection<int, Application> $applications
     * @return array<int, array{id: int, application_id: string, job: string, status: string, applied_at: ?string, offer_status: string, offer_sent_at: ?string, onboarding_status: string, placement_status: string}>
     */
    private function buildApplicantApplicationsList(\Illuminate\Support\Collection $applications): array
    {
        return $applications->take(10)->map(fn ($application): array => [
            'id' => (int) $application->id,
            'application_id' => (string) $application->application_id,
            'job' => (string) ($application->job?->title ?? 'Unknown Job'),
            'status' => (string) $application->status,
            'applied_at' => optional($application->applied_at)->format('Y-m-d'),
            'offer_status' => (string) ($application->offer_status ?? ''),
            'offer_sent_at' => optional($application->offer_sent_at)->format('Y-m-d H:i'),
            'onboarding_status' => (string) ($application->onboarding_status ?? ''),
            'placement_status' => (string) ($application->placement_status ?? ''),
        ])->all();
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
