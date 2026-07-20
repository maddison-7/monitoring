<?php

namespace App\Http\Controllers;

use App\Models\AiScore;
use App\Models\Application;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Interview;
use App\Models\Job;
use App\Models\LoginHistory;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function governance(): View
    {
        $statusCandidates = Application::query()
            ->with(['aiScore:id,application_id,recommendation_level', 'job:id,title', 'applicant.user:id,name'])
            ->whereIn('status', ['submitted', 'pending', 'review', 'reviewed', 'under_review', 'ai_processing'])
            ->whereHas('aiScore')
            ->latest('applied_at')
            ->limit(200)
            ->get();

        $mismatchRows = $statusCandidates
            ->map(function (Application $application): ?array {
                $suggested = $this->mapRecommendationToStatus((string) ($application->aiScore?->recommendation_level ?? ''));
                if ($suggested === strtolower((string) $application->status)) {
                    return null;
                }

                return [
                    'application_id' => (string) $application->application_id,
                    'candidate' => (string) ($application->applicant?->user?->name ?? 'Unknown Candidate'),
                    'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                    'ai_recommendation' => (string) ($application->aiScore?->recommendation_level ?? 'N/A'),
                    'current_status' => strtoupper((string) $application->status),
                    'suggested_status' => strtoupper($suggested),
                ];
            })
            ->filter()
            ->values();

        $stalePublishedJobs = Job::query()
            ->with('hrOfficer:id,name')
            ->publishedExpired()
            ->orderBy('application_deadline')
            ->limit(15)
            ->get()
            ->map(fn (Job $job): array => [
                'title' => (string) $job->title,
                'owner' => (string) ($job->hrOfficer?->name ?? 'Unknown Recruiter'),
                'deadline' => optional($job->application_deadline)->format('Y-m-d H:i') ?? 'N/A',
            ])
            ->all();

        $purgeableClosedJobs = $this->purgeableClosedJobsQuery()
            ->with('hrOfficer:id,name')
            ->orderBy('application_deadline')
            ->limit(15)
            ->get()
            ->map(fn (Job $job): array => [
                'title' => (string) $job->title,
                'owner' => (string) ($job->hrOfficer?->name ?? 'Unknown Recruiter'),
                'deadline' => optional($job->application_deadline)->format('Y-m-d H:i') ?? 'N/A',
                'applications' => (string) $job->applications()->count(),
            ])
            ->all();

        $staleInterviews = Interview::query()
            ->with(['application.applicant.user:id,name', 'application.job:id,title'])
            ->whereIn('status', ['scheduled', 'invitation_sent'])
            ->where('scheduled_at', '<', now()->subDay())
            ->orderBy('scheduled_at')
            ->limit(15)
            ->get()
            ->map(fn (Interview $interview): array => [
                'candidate' => (string) ($interview->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('Y-m-d H:i') ?? 'N/A',
                'status' => strtoupper((string) $interview->status),
            ])
            ->all();

        return $this->renderModule(
            'Admin Governance',
            'Governance',
            'Operational controls for the unified recruitment pipeline: AI status integrity, expired vacancy closure, and stale interview cleanup.',
            [
                ['title' => 'AI status integrity', 'description' => 'Sync application statuses with AI recommendations for pending/review states.'],
                ['title' => 'Vacancy lifecycle control', 'description' => 'Close published jobs automatically when deadlines have passed.'],
                ['title' => 'Interview hygiene', 'description' => 'Cancel stale interviews to keep recruiter queues clean and actionable.'],
            ],
            'admin.governance',
            'governance',
            [
                ['label' => 'Pending AI mismatches', 'value' => (string) $mismatchRows->count()],
                ['label' => 'Expired published jobs', 'value' => (string) count($stalePublishedJobs)],
                ['label' => 'Purgeable closed jobs', 'value' => (string) count($purgeableClosedJobs)],
                ['label' => 'Stale interviews', 'value' => (string) count($staleInterviews)],
                ['label' => 'AI score rows', 'value' => (string) AiScore::query()->count()],
            ],
            [
                ['label' => 'Application ID', 'key' => 'application_id'],
                ['label' => 'Candidate', 'key' => 'candidate'],
                ['label' => 'Job', 'key' => 'job'],
                ['label' => 'AI Recommendation', 'key' => 'ai_recommendation'],
                ['label' => 'Current Status', 'key' => 'current_status'],
                ['label' => 'Suggested Status', 'key' => 'suggested_status'],
            ],
            $mismatchRows->all(),
            [],
            [
                'stalePublishedJobs' => $stalePublishedJobs,
                'purgeableClosedJobs' => $purgeableClosedJobs,
                'staleInterviews' => $staleInterviews,
            ]
        );
    }

    public function syncAiStatuses(Request $request): RedirectResponse
    {
        $reviewed = 0;

        Application::query()
            ->with(['aiScore:id,application_id,recommendation_level'])
            ->whereIn('status', ['submitted', 'pending', 'review', 'reviewed', 'under_review', 'ai_processing'])
            ->whereHas('aiScore')
            ->chunkById(200, function ($applications) use (&$reviewed): void {
                $reviewed += $applications->count();
            });

        return redirect()->route('admin.governance')
            ->with('success', 'AI synchronization completed. HR decision gate active: no workflow statuses were auto-updated. Reviewed applications: ' . $reviewed . '.');
    }

    public function closeExpiredJobs(Request $request): RedirectResponse
    {
        $closed = Job::query()
            ->publishedExpired()
            ->update(['status' => 'closed']);

        return redirect()->route('admin.governance')
            ->with('success', 'Expired vacancy cleanup completed. Closed jobs: ' . $closed . '.');
    }

    public function purgeClosedJobs(Request $request): RedirectResponse
    {
        $deleted = 0;

        $this->purgeableClosedJobsQuery()
            ->select(['id'])
            ->chunkById(100, function ($jobs) use (&$deleted): void {
                foreach ($jobs as $job) {
                    Job::query()->whereKey((int) $job->id)->delete();
                    $deleted++;
                }
            });

        return redirect()->route('admin.governance')
            ->with('success', 'Closed vacancy purge completed. Deleted jobs and related records: ' . $deleted . '.');
    }

    public function closeStaleInterviews(Request $request): RedirectResponse
    {
        $closedInterviews = 0;
        $updatedApplications = 0;

        Interview::query()
            ->whereIn('status', ['scheduled', 'invitation_sent'])
            ->where('scheduled_at', '<', now()->subDay())
            ->select(['id', 'application_id', 'result_notes'])
            ->chunkById(200, function ($interviews) use (&$closedInterviews, &$updatedApplications): void {
                foreach ($interviews as $interview) {
                    $note = trim((string) ($interview->result_notes ?? '') . ' | Auto-cancelled by admin governance cleanup.');

                    Interview::query()->whereKey((int) $interview->id)->update([
                        'status' => 'cancelled',
                        'result_notes' => $note,
                    ]);
                    $closedInterviews++;

                    $affected = Application::query()
                        ->where('id', (int) $interview->application_id)
                        ->where('status', 'interview_scheduled')
                        ->update(['status' => 'review']);

                    $updatedApplications += (int) $affected;
                }
            });

        return redirect()->route('admin.governance')
            ->with('success', 'Stale interview cleanup completed. Interviews cancelled: ' . $closedInterviews . ', applications moved to REVIEW: ' . $updatedApplications . '.');
    }

    public function recruiters(): View
    {
        $recruiters = User::query()
            ->whereIn('role', ['recruiter', 'hr_officer'])
            ->withCount('jobs')
            ->withCount('interviews')
            ->addSelect([
                'applications_managed_count' => Application::query()
                    ->selectRaw('COUNT(*)')
                    ->join('jobs', 'jobs.id', '=', 'applications.job_id')
                    ->whereColumn('jobs.hr_officer_id', 'users.id'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $applicants = User::query()
            ->where('role', 'applicant')
            ->withCount('applications')
            ->addSelect([
                'interviews_count' => Interview::query()
                    ->selectRaw('COUNT(*)')
                    ->join('applications', 'applications.id', '=', 'interviews.application_id')
                    ->join('applicants', 'applicants.id', '=', 'applications.applicant_id')
                    ->whereColumn('applicants.user_id', 'users.id'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $totalRecruiters = $recruiters->count();
        $totalJobs = (int) $recruiters->sum('jobs_count');
        $totalManagedApplications = (int) $recruiters->sum('applications_managed_count');
        $totalInterviews = (int) $recruiters->sum('interviews_count');
        $totalApplicants = $applicants->count();
        $totalApplicantApplications = (int) $applicants->sum('applications_count');
        $totalApplicantInterviews = (int) $applicants->sum('interviews_count');

        return $this->renderModule(
            'Recruiter Management',
            'Admin control',
            'Create, update, secure, and retire recruiter accounts while monitoring operational output.',
            [
                ['title' => 'Account governance', 'description' => 'Admin can create recruiters, edit profile identity, reset temporary passwords, and remove access.'],
                ['title' => 'Operational monitoring', 'description' => 'Live table tracks each recruiter by jobs created and resumes screened.'],
            ],
            'admin.recruiters',
            'recruiters',
            [
                ['label' => 'Total recruiters', 'value' => (string) $totalRecruiters],
                ['label' => 'Total applicants', 'value' => (string) $totalApplicants],
                ['label' => 'Jobs created', 'value' => (string) $totalJobs],
                ['label' => 'Applications managed', 'value' => (string) $totalManagedApplications],
                ['label' => 'Interviews handled', 'value' => (string) ($totalInterviews + $totalApplicantInterviews)],
                ['label' => 'Applicant applications', 'value' => (string) $totalApplicantApplications],
            ],
            [
                ['label' => 'Recruiter', 'key' => 'name'],
                ['label' => 'Email', 'key' => 'email'],
                ['label' => 'Jobs', 'key' => 'jobs'],
                ['label' => 'Applications', 'key' => 'applications'],
                ['label' => 'Interviews', 'key' => 'interviews'],
                ['label' => 'Joined', 'key' => 'joined'],
            ],
            $recruiters->map(fn (User $recruiter): array => [
                'name' => $recruiter->name,
                'email' => $recruiter->email,
                'jobs' => (string) $recruiter->jobs_count,
                'applications' => (string) ($recruiter->applications_managed_count ?? 0),
                'interviews' => (string) $recruiter->interviews_count,
                'joined' => (string) $recruiter->created_at?->format('Y-m-d'),
            ])->all(),
            [],
            [
                'recruiterRecords' => $recruiters->map(fn (User $recruiter): array => [
                    'id' => $recruiter->id,
                    'first_name' => (string) ($recruiter->first_name ?? ''),
                    'last_name' => (string) ($recruiter->last_name ?? ''),
                    'name' => $recruiter->name,
                    'email' => $recruiter->email,
                    'jobs' => (int) $recruiter->jobs_count,
                    'screenings' => (int) ($recruiter->applications_managed_count ?? 0),
                    'profile_picture' => $recruiter->profile_picture,
                ])->all(),
                'applicantRecords' => $applicants->map(fn (User $applicant): array => [
                    'id' => $applicant->id,
                    'first_name' => (string) ($applicant->first_name ?? ''),
                    'last_name' => (string) ($applicant->last_name ?? ''),
                    'name' => $applicant->name,
                    'email' => $applicant->email,
                    'applications' => (int) $applicant->applications_count,
                    'interviews' => (int) ($applicant->interviews_count ?? 0),
                    'profile_picture' => $applicant->profile_picture,
                ])->all(),
            ]
        );
    }

    public function storeRecruiter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
            'role' => 'recruiter',
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Recruiter account created.');
    }

    public function updateRecruiter(Request $request, User $recruiter): RedirectResponse
    {
        $this->ensureRecruiter($recruiter);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($recruiter->id)],
        ]);

        $recruiter->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Recruiter profile updated.');
    }

    public function resetRecruiterPassword(Request $request, User $recruiter): RedirectResponse
    {
        $this->ensureRecruiter($recruiter);

        $validated = $request->validate([
            'temporary_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $recruiter->update([
            'password' => Hash::make($validated['temporary_password']),
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Temporary password updated for recruiter.');
    }

    public function deleteRecruiter(User $recruiter): RedirectResponse
    {
        $this->ensureRecruiter($recruiter);
        $recruiter->delete();

        return redirect()->route('admin.recruiters')->with('success', 'Recruiter account removed.');
    }

    public function storeApplicant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
            'role' => 'applicant',
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Applicant account created.');
    }

    public function updateApplicant(Request $request, User $applicant): RedirectResponse
    {
        $this->ensureApplicant($applicant);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($applicant->id)],
        ]);

        $applicant->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Applicant profile updated.');
    }

    public function resetApplicantPassword(Request $request, User $applicant): RedirectResponse
    {
        $this->ensureApplicant($applicant);

        $validated = $request->validate([
            'temporary_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $applicant->update([
            'password' => Hash::make($validated['temporary_password']),
        ]);

        return redirect()->route('admin.recruiters')->with('success', 'Temporary password updated for applicant.');
    }

    public function deleteApplicant(User $applicant): RedirectResponse
    {
        $this->ensureApplicant($applicant);
        $applicant->delete();

        return redirect()->route('admin.recruiters')->with('success', 'Applicant account removed.');
    }

    public function impersonateUser(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        abort_unless((string) ($admin?->role ?? '') === 'admin', 403);

        abort_if((int) ($admin?->id ?? 0) === (int) $user->id, 422, 'You are already signed in with this account.');

        abort_unless(in_array((string) $user->role, ['recruiter', 'hr_officer', 'applicant'], true), 422);

        $request->session()->put('impersonator_id', (int) $admin->id);
        $request->session()->put('impersonator_name', (string) $admin->name);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'You are now signed in as ' . $user->name . '.');
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->get('impersonator_id', 0);
        if ($impersonatorId <= 0) {
            return back()->with('error', 'No active admin impersonation session was found.');
        }

        $admin = User::query()->find($impersonatorId);
        if (!$admin instanceof User || (string) $admin->role !== 'admin') {
            Auth::logout();
            $request->session()->forget(['impersonator_id', 'impersonator_name']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'The original admin account is unavailable.']);
        }

        Auth::login($admin);
        $request->session()->forget(['impersonator_id', 'impersonator_name']);
        $request->session()->regenerate();

        return redirect()->route('panel.admin')->with('success', 'Returned to admin account.');
    }

    public function jobs(): View
    {
        $jobs = Job::query()
            ->with(['hrOfficer:id,name,email', 'department:id,name'])
            ->withCount('applications')
            ->latest()
            ->get();

        $totalJobs = $jobs->count();
        $totalApplications = (int) $jobs->sum('applications_count');
        $avgApplicationsPerJob = $totalJobs > 0 ? round($totalApplications / $totalJobs, 1) : 0;

        return $this->renderModule(
            'Global Job Oversight',
            'Oversight',
            'Review every job posting with recruiter ownership, recency, and candidate pipeline size.',
            [
                ['title' => 'Cross-recruiter visibility', 'description' => 'All recruiter-created job records are listed with candidate totals.'],
                ['title' => 'Posting governance', 'description' => 'Use this view to identify stale postings or overloaded requisitions.'],
            ],
            'admin.jobs',
            'jobs',
            [
                ['label' => 'Total jobs', 'value' => (string) $totalJobs],
                ['label' => 'Total applications', 'value' => (string) $totalApplications],
                ['label' => 'Avg applications/job', 'value' => (string) $avgApplicationsPerJob],
            ],
            [
                ['label' => 'Job title', 'key' => 'title'],
                ['label' => 'Recruiter', 'key' => 'recruiter'],
                ['label' => 'Department', 'key' => 'department'],
                ['label' => 'Applications', 'key' => 'candidates'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Created', 'key' => 'created'],
            ],
            $jobs->map(fn (Job $job): array => [
                'title' => $job->title,
                'recruiter' => (string) ($job->hrOfficer?->name ?? 'Unknown'),
                'department' => (string) ($job->department?->name ?? 'N/A'),
                'candidates' => (string) $job->applications_count,
                'status' => strtoupper((string) $job->status),
                'created' => (string) $job->created_at?->format('Y-m-d'),
            ])->all()
        );
    }

    public function analytics(): View
    {
        $totalJobs = Job::count();
        $totalCandidates = (int) Application::query()->distinct('applicant_id')->count('applicant_id');
        $totalApplications = Application::count();
        $averageScore = round((float) (AiScore::query()->avg('match_percentage') ?? 0), 1);
        $shortlisted = Application::query()->where('status', 'shortlisted')->count();
        $shortlistRate = $totalApplications > 0 ? round(($shortlisted / $totalApplications) * 100, 1) : 0;

        $topRecruiters = User::query()
            ->whereIn('role', ['recruiter', 'hr_officer'])
            ->withCount('jobs')
            ->addSelect([
                'applications_managed_count' => Application::query()
                    ->selectRaw('COUNT(*)')
                    ->join('jobs', 'jobs.id', '=', 'applications.job_id')
                    ->whereColumn('jobs.hr_officer_id', 'users.id'),
            ])
            ->orderByDesc('applications_managed_count')
            ->limit(5)
            ->get();

        return $this->renderModule(
            'System Analytics',
            'Analytics',
            'Track real screening KPIs from live candidate scoring and recruiter activity.',
            [
                ['title' => 'Screening KPIs', 'description' => 'Live metrics are calculated from candidate and job records in your database.'],
                ['title' => 'Top contributors', 'description' => 'The table below ranks recruiters by screening throughput.'],
            ],
            'admin.analytics',
            'analytics',
            [
                ['label' => 'Jobs tracked', 'value' => (string) $totalJobs],
                ['label' => 'Applicants tracked', 'value' => (string) $totalCandidates],
                ['label' => 'Applications', 'value' => (string) $totalApplications],
                ['label' => 'Average match score', 'value' => $averageScore . '%'],
                ['label' => 'Shortlist rate', 'value' => $shortlistRate . '%'],
            ],
            [
                ['label' => 'Recruiter', 'key' => 'name'],
                ['label' => 'Email', 'key' => 'email'],
                ['label' => 'Jobs', 'key' => 'jobs'],
                ['label' => 'Applications', 'key' => 'screenings'],
            ],
            $topRecruiters->map(fn (User $user): array => [
                'name' => $user->name,
                'email' => $user->email,
                'jobs' => (string) $user->jobs_count,
                'screenings' => (string) ($user->applications_managed_count ?? 0),
            ])->all()
        );
    }

    public function reports(): View
    {
        $rows = $this->buildAdminReportRows();

        return $this->renderModule(
            'Organization Reports',
            'Reporting',
            'Summarize recruitment throughput and shortlist performance by reporting period.',
            [
                ['title' => 'Weekly and monthly summaries', 'description' => 'Compare throughput over fixed windows to spot capacity changes.'],
                ['title' => 'Quarter trend check', 'description' => 'Track strategic hiring momentum within the current quarter.'],
            ],
            'admin.reports',
            'reports',
            [
                ['label' => 'Report windows', 'value' => (string) count($rows)],
                ['label' => 'Latest jobs (30d)', 'value' => $rows[1]['jobs'] ?? '0'],
                ['label' => 'Latest applications (30d)', 'value' => $rows[1]['screened'] ?? '0'],
            ],
            [
                ['label' => 'Period', 'key' => 'period'],
                ['label' => 'Jobs', 'key' => 'jobs'],
                ['label' => 'Screened', 'key' => 'screened'],
                ['label' => 'Shortlisted', 'key' => 'shortlisted'],
                ['label' => 'Shortlist rate', 'key' => 'shortlist_rate'],
                ['label' => 'Average score', 'key' => 'avg_score'],
                ['label' => 'Top recommendation', 'key' => 'top_recommendation'],
                ['label' => 'Active recruiters', 'key' => 'active_recruiters'],
            ],
            $rows
        );
    }

    public function exportReportsCsv()
    {
        $rows = collect($this->buildAdminReportRows())
            ->map(fn (array $row): array => [
                'Period' => $row['period'],
                'Jobs' => $row['jobs'],
                'Screened' => $row['screened'],
                'Shortlisted' => $row['shortlisted'],
                'Shortlist Rate' => $row['shortlist_rate'],
                'Average Score' => $row['avg_score'],
                'Top Recommendation' => $row['top_recommendation'],
                'Active Recruiters' => $row['active_recruiters'],
            ])
            ->values()
            ->all();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admin-reports-' . now()->format('Ymd-His') . '.csv"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Period', 'Jobs', 'Screened', 'Shortlisted', 'Shortlist Rate', 'Average Score', 'Top Recommendation', 'Active Recruiters']);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['Period'],
                    $row['Jobs'],
                    $row['Screened'],
                    $row['Shortlisted'],
                    $row['Shortlist Rate'],
                    $row['Average Score'],
                    $row['Top Recommendation'],
                    $row['Active Recruiters'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportReportsExcel()
    {
        $rows = collect($this->buildAdminReportRows())
            ->map(fn (array $row): array => [
                'Period' => $row['period'],
                'Jobs' => $row['jobs'],
                'Screened' => $row['screened'],
                'Shortlisted' => $row['shortlisted'],
                'Shortlist Rate' => $row['shortlist_rate'],
                'Average Score' => $row['avg_score'],
                'Top Recommendation' => $row['top_recommendation'],
                'Active Recruiters' => $row['active_recruiters'],
            ])
            ->values()
            ->all();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="admin-reports-' . now()->format('Ymd-His') . '.xls"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Period', 'Jobs', 'Screened', 'Shortlisted', 'Shortlist Rate', 'Average Score', 'Top Recommendation', 'Active Recruiters']);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['Period'],
                    $row['Jobs'],
                    $row['Screened'],
                    $row['Shortlisted'],
                    $row['Shortlist Rate'],
                    $row['Average Score'],
                    $row['Top Recommendation'],
                    $row['Active Recruiters'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportReportsPdf()
    {
        $rows = collect($this->buildAdminReportRows())
            ->map(fn (array $row): array => [
                'Period' => $row['period'],
                'Jobs' => $row['jobs'],
                'Screened' => $row['screened'],
                'Shortlisted' => $row['shortlisted'],
                'Shortlist Rate' => $row['shortlist_rate'],
                'Average Score' => $row['avg_score'],
                'Top Recommendation' => $row['top_recommendation'],
                'Active Recruiters' => $row['active_recruiters'],
            ])
            ->values()
            ->all();

        $columns = ['Period', 'Jobs', 'Screened', 'Shortlisted', 'Shortlist Rate', 'Average Score', 'Top Recommendation', 'Active Recruiters'];

        if (class_exists('Barryvdh\\DomPDF\\Facade')) {
            $pdf = \Barryvdh\DomPDF\Facade::loadView('exports.admin-table-pdf', [
                'title' => 'Admin Reports',
                'columns' => $columns,
                'rows' => $rows,
                'generatedAt' => now()->format('Y-m-d H:i'),
            ]);

            return $pdf->download('admin-reports-' . now()->format('Ymd-His') . '.pdf');
        }

        $html = view('exports.admin-table-pdf', [
            'title' => 'Admin Reports',
            'columns' => $columns,
            'rows' => $rows,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admin-reports-' . now()->format('Ymd-His') . '.html"',
        ]);
    }

    public function audit(): View
    {
        $timeline = $this->buildAuditTimeline(25);
        $settingsEventsCount = (int) AppSetting::query()
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $loginHistoryColumns = [
            ['key' => 'user', 'label' => 'User'],
            ['key' => 'role', 'label' => 'Role'],
            ['key' => 'ip', 'label' => 'IP Address'],
            ['key' => 'time', 'label' => 'Logged In At'],
        ];

        $loginHistoryRows = LoginHistory::query()
            ->with('user:id,name,role')
            ->latest('logged_in_at')
            ->limit(20)
            ->get()
            ->map(fn (LoginHistory $login): array => [
                'user' => (string) ($login->user?->name ?? 'Unknown'),
                'role' => (string) ($login->user?->role ?? 'N/A'),
                'ip' => (string) ($login->ip_address ?? 'N/A'),
                'time' => (string) $login->logged_in_at?->format('Y-m-d H:i'),
            ])
            ->all();

        return $this->renderModule(
            'Audit',
            'Audit',
            'Inspect login history, user activities, system actions, data changes, recruiter actions, and admin actions generated from live platform events.',
            [
                ['title' => 'Action timeline', 'description' => 'Events include job creation and resume processing with actor attribution.'],
                ['title' => 'Compliance readiness', 'description' => 'Use this chronological log to support reviews and investigations.'],
            ],
            'admin.audit',
            'audit',
            [
                ['label' => 'Recent events', 'value' => (string) count($timeline)],
                ['label' => 'Login events', 'value' => (string) LoginHistory::query()->count()],
                ['label' => 'Job events', 'value' => (string) AuditLog::query()->where('action', 'like', 'job.%')->count()],
                ['label' => 'Application events', 'value' => (string) AuditLog::query()->where('action', 'like', 'application.%')->count()],
                ['label' => 'Settings events (30d)', 'value' => (string) $settingsEventsCount],
            ],
            $loginHistoryColumns,
            $loginHistoryRows,
            $timeline
        );
    }

    public function exportAuditCsv()
    {
        $rows = collect($this->buildAuditTimeline(500));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admin-audit-timeline-' . now()->format('Ymd-His') . '.csv"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Event', 'Actor', 'Details', 'Time']);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['event'] ?? '',
                    $row['actor'] ?? '',
                    $row['details'] ?? '',
                    $row['time'] ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAuditPdf()
    {
        $rows = collect($this->buildAuditTimeline(250))
            ->map(fn (array $row): array => [
                'Event' => $row['event'],
                'Actor' => $row['actor'],
                'Details' => $row['details'],
                'Time' => $row['time'],
            ])
            ->values()
            ->all();

        $columns = ['Event', 'Actor', 'Details', 'Time'];

        if (class_exists('Barryvdh\\DomPDF\\Facade')) {
            $pdf = \Barryvdh\DomPDF\Facade::loadView('exports.admin-table-pdf', [
                'title' => 'Admin Audit Timeline',
                'columns' => $columns,
                'rows' => $rows,
                'generatedAt' => now()->format('Y-m-d H:i'),
            ]);

            return $pdf->download('admin-audit-timeline-' . now()->format('Ymd-His') . '.pdf');
        }

        $html = view('exports.admin-table-pdf', [
            'title' => 'Admin Audit Timeline',
            'columns' => $columns,
            'rows' => $rows,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admin-audit-timeline-' . now()->format('Ymd-His') . '.html"',
        ]);
    }

    public function apiUsage(): View
    {
        $screenedCount = Application::query()->count();
        $jobsCount = Job::query()->count();
        $scoredCount = AiScore::query()->whereNotNull('match_percentage')->count();
        $shortlistedCount = Application::query()->where('status', 'shortlisted')->count();
        $avgScore = round((float) (AiScore::query()->avg('match_percentage') ?? 0), 1);

        $isGeminiConfigured = (string) config('services.gemini.api_key', '') !== '';
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');

        $windowStart = Carbon::now()->subDays(6)->startOfDay();

        $dailyTotals = Application::query()
            ->leftJoin('ai_scores', 'ai_scores.application_id', '=', 'applications.id')
            ->selectRaw('DATE(applications.applied_at) as day, COUNT(applications.id) as total, AVG(ai_scores.match_percentage) as avg_score')
            ->where('applications.applied_at', '>=', $windowStart)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyShortlisted = Application::query()
            ->selectRaw('DATE(applied_at) as day, COUNT(*) as total')
            ->where('applied_at', '>=', $windowStart)
            ->where('status', 'shortlisted')
            ->groupBy('day')
            ->pluck('total', 'day');

        $dailyRows = collect(range(0, 6))
            ->map(function (int $offset) use ($dailyTotals, $dailyShortlisted): array {
                $date = Carbon::now()->subDays(6 - $offset)->toDateString();
                $totals = $dailyTotals->get($date);
                $count = (int) ($totals->total ?? 0);
                $avg = round((float) ($totals->avg_score ?? 0), 1);
                $shortlisted = (int) ($dailyShortlisted[$date] ?? 0);

                return [
                    'day' => Carbon::parse($date)->format('Y-m-d'),
                    'processed' => (string) $count,
                    'shortlisted' => (string) $shortlisted,
                    'shortlist_rate' => $count > 0 ? round(($shortlisted / $count) * 100, 1) . '%' : '0%',
                    'avg_score' => $avg . '%',
                ];
            })
            ->all();

        return $this->renderModule(
            'API Usage',
            'API Monitoring',
            'Monitor AI API requests, success/failure rates, and estimated cost patterns.',
            [
                ['title' => 'Usage volume', 'description' => 'Measure request totals across modules and time ranges.'],
                ['title' => 'Reliability and spend', 'description' => 'Review failures, retries, and cost visibility.'],
            ],
            'admin.api',
            'api',
            [
                ['label' => 'Applications processed', 'value' => (string) $screenedCount],
                ['label' => 'Jobs evaluated', 'value' => (string) $jobsCount],
                ['label' => 'Scored applications', 'value' => (string) $scoredCount],
                ['label' => 'Shortlisted', 'value' => (string) $shortlistedCount],
                ['label' => 'Average score', 'value' => $avgScore . '%'],
                ['label' => 'Provider status', 'value' => $isGeminiConfigured ? 'Gemini configured' : 'Gemini key missing'],
                ['label' => 'Model', 'value' => $model],
            ],
            [
                ['label' => 'Day', 'key' => 'day'],
                ['label' => 'Processed', 'key' => 'processed'],
                ['label' => 'Shortlisted', 'key' => 'shortlisted'],
                ['label' => 'Shortlist rate', 'key' => 'shortlist_rate'],
                ['label' => 'Average score', 'key' => 'avg_score'],
            ],
            $dailyRows
        );
    }

    public function system(): View
    {
        $settings = $this->getSystemSettings();

        $liveSettingsRows = AppSetting::query()
            ->whereIn('key', [
                'scoring_skills_weight',
                'scoring_experience_weight',
                'scoring_education_weight',
                'scoring_domain_weighting_enabled',
                'shortlist_threshold',
                'resume_max_files',
                'resume_max_file_size_mb',
                'session_timeout_minutes',
                'allowed_resume_types',
            ])
            ->orderBy('key')
            ->get()
            ->map(function (AppSetting $setting): array {
                $value = (string) $setting->value;
                if ($setting->key === 'scoring_domain_weighting_enabled') {
                    $value = $value === '0' ? 'Disabled' : 'Enabled';
                }

                return [
                    'key' => $setting->key,
                    'value' => $value,
                    'updated_at' => (string) $setting->updated_at?->format('Y-m-d H:i'),
                ];
            })
            ->values()
            ->all();

        $latestSettingUpdate = AppSetting::query()->max('updated_at');

        return $this->renderModule(
            'System Configuration',
            'Configuration',
            'Configure platform defaults for scoring and operational settings.',
            [
                ['title' => 'Scoring defaults', 'description' => 'Set organization-level baseline weights for skills, experience, and education.'],
                ['title' => 'Platform policy settings', 'description' => 'Configure file constraints, allowed types, and session policy values.'],
            ],
            'admin.system',
            'system',
            [
                ['label' => 'Shortlist threshold', 'value' => $settings['shortlist_threshold'] . '%'],
                ['label' => 'Resume max files', 'value' => (string) $settings['resume_max_files']],
                ['label' => 'Max file size', 'value' => $settings['resume_max_file_size_mb'] . ' MB'],
                ['label' => 'Session timeout', 'value' => $settings['session_timeout_minutes'] . ' min'],
                ['label' => 'Tracked settings', 'value' => (string) count($liveSettingsRows)],
                ['label' => 'Last settings update', 'value' => $latestSettingUpdate ? Carbon::parse((string) $latestSettingUpdate)->format('Y-m-d H:i') : 'N/A'],
            ],
            [
                ['label' => 'Setting key', 'key' => 'key'],
                ['label' => 'Current value', 'key' => 'value'],
                ['label' => 'Updated at', 'key' => 'updated_at'],
            ],
            $liveSettingsRows,
            [],
            [
                'systemSettings' => $settings,
            ]
        );
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scoring_skills_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'scoring_experience_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'scoring_education_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'scoring_domain_weighting_enabled' => ['required', 'boolean'],
            'shortlist_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'resume_max_files' => ['required', 'integer', 'min:1', 'max:25'],
            'resume_max_file_size_mb' => ['required', 'integer', 'min:1', 'max:25'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'allowed_resume_types' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9,]+$/i'],
        ]);

        $weightTotal =
            (int) $validated['scoring_skills_weight'] +
            (int) $validated['scoring_experience_weight'] +
            (int) $validated['scoring_education_weight'];

        if ($weightTotal !== 100) {
            return back()
                ->withErrors(['scoring_skills_weight' => 'Skills, experience, and education weights must total 100.'])
                ->withInput();
        }

        $allowedTypes = collect(explode(',', strtolower((string) $validated['allowed_resume_types'])))
            ->map(fn (string $type): string => trim($type))
            ->filter(fn (string $type): bool => $type !== '')
            ->unique()
            ->values()
            ->implode(',');

        $settingsToSave = [
            'scoring_skills_weight' => (string) $validated['scoring_skills_weight'],
            'scoring_experience_weight' => (string) $validated['scoring_experience_weight'],
            'scoring_education_weight' => (string) $validated['scoring_education_weight'],
            'scoring_domain_weighting_enabled' => ((bool) $validated['scoring_domain_weighting_enabled']) ? '1' : '0',
            'shortlist_threshold' => (string) $validated['shortlist_threshold'],
            'resume_max_files' => (string) $validated['resume_max_files'],
            'resume_max_file_size_mb' => (string) $validated['resume_max_file_size_mb'],
            'session_timeout_minutes' => (string) $validated['session_timeout_minutes'],
            'allowed_resume_types' => $allowedTypes,
        ];

        foreach ($settingsToSave as $key => $value) {
            AppSetting::setValue($key, $value);
        }

        return redirect()->route('admin.system')->with('success', 'System configuration updated successfully.');
    }

    /**
     * @param array<int, array{title: string, description: string}> $cards
     */
    private function renderModule(
        string $pageHeading,
        string $badge,
        string $summary,
        array $cards,
        string $activeNav,
        string $moduleKey,
        array $stats = [],
        array $tableColumns = [],
        array $tableRows = [],
        array $timeline = [],
        array $moduleData = []
    ): View
    {
        return view('admin.module', [
            'pageTitle' => $pageHeading,
            'pageHeading' => $pageHeading,
            'activeNav' => $activeNav,
            'moduleKey' => $moduleKey,
            'badge' => $badge,
            'summary' => $summary,
            'cards' => $cards,
            'stats' => $stats,
            'tableColumns' => $tableColumns,
            'tableRows' => $tableRows,
            'timeline' => $timeline,
            'moduleData' => $moduleData,
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildAdminReportRows(): array
    {
        $now = Carbon::now();
        $periods = [
            [
                'label' => 'Last 7 days',
                'start' => $now->copy()->subDays(7),
                'end' => $now,
            ],
            [
                'label' => 'Last 30 days',
                'start' => $now->copy()->subDays(30),
                'end' => $now,
            ],
            [
                'label' => 'Current quarter',
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now,
            ],
        ];

        $rows = [];
        foreach ($periods as $period) {
            $jobs = Job::query()
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count();

            $applications = Application::query()
                ->whereBetween('applied_at', [$period['start'], $period['end']])
                ->count();

            $shortlisted = Application::query()
                ->whereBetween('applied_at', [$period['start'], $period['end']])
                ->where('status', 'shortlisted')
                ->count();

            $avgScore = round((float) (AiScore::query()
                ->whereHas('application', function ($query) use ($period): void {
                    $query->whereBetween('applied_at', [$period['start'], $period['end']]);
                })
                ->avg('match_percentage') ?? 0), 1);

            $topRecommendation = AiScore::query()
                ->select('recommendation_level', DB::raw('COUNT(*) as total'))
                ->whereHas('application', function ($query) use ($period): void {
                    $query->whereBetween('applied_at', [$period['start'], $period['end']]);
                })
                ->whereNotNull('recommendation_level')
                ->groupBy('recommendation_level')
                ->orderByDesc('total')
                ->value('recommendation_level');

            $activeRecruiters = Job::query()
                ->whereHas('applications', function ($query) use ($period): void {
                    $query->whereBetween('applied_at', [$period['start'], $period['end']]);
                })
                ->distinct('hr_officer_id')
                ->count('hr_officer_id');

            $rows[] = [
                'period' => $period['label'],
                'jobs' => (string) $jobs,
                'screened' => (string) $applications,
                'shortlisted' => (string) $shortlisted,
                'shortlist_rate' => $applications > 0 ? round(($shortlisted / $applications) * 100, 1) . '%' : '0%',
                'avg_score' => $avgScore . '%',
                'top_recommendation' => (string) ($topRecommendation ?: 'N/A'),
                'active_recruiters' => (string) $activeRecruiters,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildAuditTimeline(int $limit): array
    {
        $auditRows = AuditLog::query()
            ->with('user:id,name,role')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'event' => (string) $log->action,
                'actor' => (string) ($log->user?->name ?? 'System'),
                'details' => (string) ($log->auditable_type ?: 'N/A') . ' #' . (string) ($log->auditable_id ?? 'N/A'),
                'time' => (string) $log->created_at?->format('Y-m-d H:i'),
            ]);

        $notificationRows = Notification::query()
            ->latest()
            ->limit(max(5, (int) floor($limit / 5)))
            ->get()
            ->map(fn (Notification $notification): array => [
                'event' => 'notification.' . (string) $notification->type,
                'actor' => 'System',
                'details' => (string) $notification->title,
                'time' => (string) $notification->created_at?->format('Y-m-d H:i'),
            ]);

        return $auditRows
            ->concat($notificationRows)
            ->sortByDesc('time')
            ->take($limit)
            ->values()
            ->all();
    }

    private function ensureRecruiter(User $user): void
    {
        abort_unless(in_array($user->role, ['recruiter', 'hr_officer'], true), 404);
    }

    private function ensureApplicant(User $user): void
    {
        abort_unless($user->role === 'applicant', 404);
    }

    /**
     * @return array<string, int|string>
     */
    private function getSystemSettings(): array
    {
        $defaults = [
            'scoring_skills_weight' => 50,
            'scoring_experience_weight' => 30,
            'scoring_education_weight' => 20,
            'scoring_domain_weighting_enabled' => 1,
            'shortlist_threshold' => 75,
            'resume_max_files' => 10,
            'resume_max_file_size_mb' => 5,
            'session_timeout_minutes' => 120,
            'allowed_resume_types' => 'pdf,doc,docx',
        ];

        $stored = AppSetting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return [
            'scoring_skills_weight' => (int) ($stored['scoring_skills_weight'] ?? $defaults['scoring_skills_weight']),
            'scoring_experience_weight' => (int) ($stored['scoring_experience_weight'] ?? $defaults['scoring_experience_weight']),
            'scoring_education_weight' => (int) ($stored['scoring_education_weight'] ?? $defaults['scoring_education_weight']),
            'scoring_domain_weighting_enabled' => ((string) ($stored['scoring_domain_weighting_enabled'] ?? (string) $defaults['scoring_domain_weighting_enabled'])) !== '0',
            'shortlist_threshold' => (int) ($stored['shortlist_threshold'] ?? $defaults['shortlist_threshold']),
            'resume_max_files' => (int) ($stored['resume_max_files'] ?? $defaults['resume_max_files']),
            'resume_max_file_size_mb' => (int) ($stored['resume_max_file_size_mb'] ?? $defaults['resume_max_file_size_mb']),
            'session_timeout_minutes' => (int) ($stored['session_timeout_minutes'] ?? $defaults['session_timeout_minutes']),
            'allowed_resume_types' => (string) ($stored['allowed_resume_types'] ?? $defaults['allowed_resume_types']),
        ];
    }

    private function purgeableClosedJobsQuery()
    {
        return Job::query()
            ->where('status', 'closed')
            ->whereDoesntHave('applications', function ($applications): void {
                $applications->where(function ($active): void {
                    $active
                        ->whereNotIn('status', ['rejected', 'offer_declined'])
                        ->where(function ($notPlaced): void {
                            $notPlaced
                                ->whereNull('placement_status')
                                ->orWhere('placement_status', '!=', 'closed');
                        });
                });
            });
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