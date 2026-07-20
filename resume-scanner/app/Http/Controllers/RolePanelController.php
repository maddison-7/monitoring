<?php

namespace App\Http\Controllers;

use App\Models\AiScore;
use App\Models\AppSetting;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\CandidateProfile;
use App\Models\Department;
use App\Models\Education;
use App\Models\Interview;
use App\Models\LoginHistory;
use App\Models\Notification;
use App\Models\JobPosting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePanelController extends Controller
{
    public function admin(Request $request): View
    {
        $dashboard = $this->buildAdminDashboardData($request);

        return view('admin.dashboard', [
            'pageTitle' => __('messages.admin_panel'),
            'pageHeading' => __('messages.admin_panel'),
            'activeNav' => 'dashboard',
            'dashboard' => $dashboard,
        ]);
    }

    public function recruiter(Request $request): RedirectResponse
    {
        return redirect()->route('hr.dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminDashboardData(Request $request): array
    {
        $totals = $this->countAdminTotals();
        $systemSettings = AppSetting::query()->pluck('value', 'key');
        $storageUsed = $this->calculateStorageUsedPercent();

        return [
            'metrics' => [
                'Total Users' => $totals['users'],
                'Active Jobs' => $totals['jobs'],
                'Applications' => $totals['applications'],
                'Recruiters' => $totals['recruiters'],
            ],
            'systemMetrics' => $this->buildAdminSystemMetrics($totals),
            'charts' => $this->buildAdminChartData(),
            'departments' => $this->buildAdminDepartmentRows(),
            'vacancyOversight' => $this->buildAdminVacancyOversight(),
            'interviews' => $this->buildAdminTodayInterviews(),
            'interviewStatus' => $this->buildAdminInterviewStatusMap(),
            'backupSummary' => $this->buildAdminBackupSummary($storageUsed),
            'notifications' => $this->buildAdminNotifications(),
            'securityLogs' => $this->buildAdminSecurityLogs(),
            'auditLogs' => $this->buildAdminAuditLogs(),
            'workflow' => $this->adminRecruitmentWorkflow(),
            'systemHealth' => $this->buildAdminSystemHealth($systemSettings, $storageUsed),
            'settings' => $this->buildAdminScoringSettings($systemSettings),
            'recentApplications' => $this->buildAdminRecentApplications(),
        ];
    }

    /**
     * @return array{users: int, recruiters: int, applicants: int, jobs: int, applications: int}
     */
    private function countAdminTotals(): array
    {
        return [
            'users' => User::query()->count(),
            'recruiters' => User::query()->where('role', 'recruiter')->count(),
            'applicants' => User::query()->where('role', 'applicant')->count(),
            'jobs' => JobPosting::query()->count(),
            'applications' => Application::query()->count(),
        ];
    }

    /**
     * @return array<string, array{labels: array<int, mixed>, values: array<int, mixed>}>
     */
    private function buildAdminChartData(): array
    {
        $applicationsPerDepartmentRows = Application::query()
            ->join('job_postings', 'job_postings.id', '=', 'applications.job_id')
            ->selectRaw("COALESCE(NULLIF(job_postings.department, ''), 'Unassigned') as department, COUNT(applications.id) as total")
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $applicationTrendLabels = [];
        $applicationTrendMap = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $key = $day->toDateString();
            $applicationTrendLabels[] = $day->format('M d');
            $applicationTrendMap[$key] = 0;
        }

        Application::query()
            ->where('applied_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->get(['applied_at'])
            ->each(function ($application) use (&$applicationTrendMap): void {
                if (!$application->applied_at) {
                    return;
                }

                $key = Carbon::parse($application->applied_at)->toDateString();
                if (array_key_exists($key, $applicationTrendMap)) {
                    $applicationTrendMap[$key]++;
                }
            });

        $qualificationMap = $this->buildQualificationDistribution(Education::query()->get(['level']));

        $aiRankBuckets = [
            '90%+' => 0,
            '70-89%' => 0,
            'Below 70%' => 0,
        ];

        foreach (AiScore::query()->pluck('match_percentage') as $score) {
            $value = (float) $score;
            if ($value >= 90) {
                $aiRankBuckets['90%+']++;
            } elseif ($value >= 70) {
                $aiRankBuckets['70-89%']++;
            } else {
                $aiRankBuckets['Below 70%']++;
            }
        }

        return [
            'applicationsPerDepartment' => [
                'labels' => $applicationsPerDepartmentRows->pluck('department')->toArray(),
                'values' => $applicationsPerDepartmentRows->pluck('total')->toArray(),
            ],
            'recruitmentTrends' => [
                'labels' => $applicationTrendLabels,
                'values' => array_values($applicationTrendMap),
            ],
            'qualificationDistribution' => [
                'labels' => array_keys($qualificationMap),
                'values' => array_values($qualificationMap),
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
    private function buildQualificationDistribution(iterable $rows): array
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
     * @return array<int, array{department: string, applications: int, jobs: int}>
     */
    private function buildAdminDepartmentRows(): array
    {
        return Department::query()
            ->leftJoin('job_postings', 'job_postings.department', '=', 'departments.name')
            ->leftJoin('applications', 'applications.job_id', '=', 'job_postings.id')
            ->selectRaw('departments.name as department, COUNT(DISTINCT applications.id) as applications_total, COUNT(DISTINCT job_postings.id) as jobs_total')
            ->groupBy('departments.name')
            ->orderByDesc('applications_total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'department' => (string) $row->department,
                'applications' => (int) $row->applications_total,
                'jobs' => (int) $row->jobs_total,
            ])
            ->all();
    }

    /**
     * @return array<int, array{title: string, department: string, candidates: int, created: ?string}>
     */
    private function buildAdminVacancyOversight(): array
    {
        return JobPosting::query()
            ->withCount('candidates')
            ->orderByDesc('candidates_count')
            ->limit(8)
            ->get(['id', 'title', 'department', 'created_at'])
            ->map(fn (JobPosting $job): array => [
                'title' => (string) $job->title,
                'department' => (string) ($job->department ?? 'Unassigned'),
                'candidates' => (int) $job->candidates_count,
                'created' => optional($job->created_at)->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{candidate: string, job: string, scheduled_at: ?string, mode: string, venue: string, status: string}>
     */
    private function buildAdminTodayInterviews(): array
    {
        return Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn (Interview $interview): array => [
                'candidate' => (string) ($interview->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('Y-m-d H:i'),
                'mode' => strtoupper((string) $interview->mode),
                'venue' => (string) ($interview->venue ?? 'N/A'),
                'status' => (string) ($interview->status ?? 'scheduled'),
            ])
            ->all();
    }

    /**
     * @return array{scheduled: int, invitation_sent: int, confirmed: int, completed: int}
     */
    private function buildAdminInterviewStatusMap(): array
    {
        $interviewStatusMap = [
            'scheduled' => 0,
            'invitation_sent' => 0,
            'confirmed' => 0,
            'completed' => 0,
        ];

        $interviewStatusRows = Interview::query()
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
     * @return array<int, array{title: string, message: string, time: ?string}>
     */
    private function buildAdminNotifications(): array
    {
        return Notification::query()->latest()->limit(6)->get()
            ->map(fn (Notification $notification): array => [
                'title' => (string) $notification->title,
                'message' => (string) $notification->message,
                'time' => optional($notification->created_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{user: string, role: string, ip: string, time: ?string}>
     */
    private function buildAdminSecurityLogs(): array
    {
        return LoginHistory::query()
            ->with('user:id,name,role')
            ->latest('logged_in_at')
            ->limit(6)
            ->get()
            ->map(fn (LoginHistory $history): array => [
                'user' => (string) ($history->user?->name ?? 'Unknown User'),
                'role' => (string) ($history->user?->role ?? 'unknown'),
                'ip' => (string) ($history->ip_address ?? 'N/A'),
                'time' => optional($history->logged_in_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{event: string, time: ?string, details: string, actor: string}>
     */
    private function buildAdminAuditLogs(): array
    {
        return AuditLog::query()
            ->with('user:id,name,role')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'event' => (string) $log->action,
                'time' => optional($log->created_at)->diffForHumans(),
                'details' => (string) ($log->user?->name ?? 'System'),
                'actor' => (string) ($log->user?->role ?? 'system'),
            ])
            ->all();
    }

    private function calculateStorageUsedPercent(): float
    {
        $storageTotal = @disk_total_space(storage_path()) ?: 0;
        $storageFree = @disk_free_space(storage_path()) ?: 0;

        return $storageTotal > 0 ? round((1 - ($storageFree / $storageTotal)) * 100, 1) : 0;
    }

    /**
     * @return array<int, array{label: string, value: string, tone: string}>
     */
    private function buildAdminSystemHealth(\Illuminate\Support\Collection $systemSettings, float $storageUsed): array
    {
        return [
            ['label' => __('messages.server_status'), 'value' => __('messages.online'), 'tone' => 'bg-emerald-100 text-emerald-700'],
            ['label' => __('messages.database'), 'value' => __('messages.connected'), 'tone' => 'bg-blue-100 text-blue-700'],
            ['label' => __('messages.evaluation_engine'), 'value' => !empty($systemSettings['openai_api_key'] ?? null) ? __('messages.active') : __('messages.configured'), 'tone' => 'bg-violet-100 text-violet-700'],
            ['label' => __('messages.storage'), 'value' => $storageUsed . '% ' . __('messages.used'), 'tone' => 'bg-amber-100 text-amber-700'],
        ];
    }

    /**
     * @return array{last_backup: string, status: string, storage: float}
     */
    private function buildAdminBackupSummary(float $storageUsed): array
    {
        return [
            'last_backup' => (string) (AppSetting::getValue('backup_last_run', 'Not yet recorded') ?? 'Not yet recorded'),
            'status' => (string) (AppSetting::getValue('backup_status', 'Healthy') ?? 'Healthy'),
            'storage' => $storageUsed,
        ];
    }

    /**
     * @param array{users: int, recruiters: int, applicants: int, jobs: int, applications: int} $totals
     * @return array<int, array{label: string, value: string}>
     */
    private function buildAdminSystemMetrics(array $totals): array
    {
        return [
            ['label' => __('messages.total_users'), 'value' => number_format($totals['users'])],
            ['label' => __('messages.active_jobs'), 'value' => number_format($totals['jobs'])],
            ['label' => __('messages.applications'), 'value' => number_format($totals['applications'])],
            ['label' => __('messages.recruiters'), 'value' => number_format($totals['recruiters'])],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function adminRecruitmentWorkflow(): array
    {
        return [
            'Manage Recruiters',
            'Configure Departments',
            'Monitor Recruitment Activities',
            'Manage Security',
            'View Analytics',
            'Generate Reports',
            'Maintain System',
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed> $systemSettings
     * @return array{shortlist_threshold: int, scoring_skills_weight: int, scoring_experience_weight: int, scoring_education_weight: int}
     */
    private function buildAdminScoringSettings(\Illuminate\Support\Collection $systemSettings): array
    {
        return [
            'shortlist_threshold' => (int) ($systemSettings['shortlist_threshold'] ?? 75),
            'scoring_skills_weight' => (int) ($systemSettings['scoring_skills_weight'] ?? 50),
            'scoring_experience_weight' => (int) ($systemSettings['scoring_experience_weight'] ?? 30),
            'scoring_education_weight' => (int) ($systemSettings['scoring_education_weight'] ?? 20),
        ];
    }

    /**
     * @return array<int, array{candidate: string, job: string, time: ?string}>
     */
    private function buildAdminRecentApplications(): array
    {
        return Application::query()
            ->with(['applicant.user', 'job'])
            ->latest('applied_at')
            ->limit(8)
            ->get()
            ->map(fn (Application $application): array => [
                'candidate' => (string) ($application->applicant?->user?->name ?? 'Unknown Applicant'),
                'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                'time' => optional($application->applied_at)->diffForHumans(),
            ])
            ->all();
    }

    private function renderPanel(Request $request, string $role): View
    {
        $user = $request->user();

        $jobBaseQuery = JobPosting::query();
        $candidateBaseQuery = CandidateProfile::query();

        if ($role === 'recruiter' && $user) {
            $jobBaseQuery->where('user_id', $user->id);
            $candidateBaseQuery->where('user_id', $user->id);
        }

        $recruiterCount = User::query()->where('role', 'recruiter')->count();
        $jobCount = (clone $jobBaseQuery)->count();
        $screenedCount = (clone $candidateBaseQuery)->count();
        $shortlistedCount = (clone $candidateBaseQuery)
            ->where(function (Builder $query): void {
                $query->where('recommendation', 'Shortlisted')
                    ->orWhere('status', 'Shortlisted');
            })
            ->count();

        $averageScore = (float) ((clone $candidateBaseQuery)->whereNotNull('match_score')->avg('match_score') ?? 0);
        $averageScore = round($averageScore, 1);

        $recentCandidates = (clone $candidateBaseQuery)
            ->select(['id', 'job_posting_id', 'match_score', 'recommendation', 'status', 'created_at'])
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->get();

        $statusBuckets = [
            'Shortlisted' => 0,
            'Recommended' => 0,
            'Needs Review' => 0,
            'Rejected' => 0,
        ];

        foreach ($recentCandidates as $candidate) {
            $recommendation = strtolower(trim((string) ($candidate->recommendation ?? '')));
            $status = strtolower(trim((string) ($candidate->status ?? '')));

            if ($recommendation === 'shortlist' || $status === 'shortlisted') {
                $statusBuckets['Shortlisted']++;
                continue;
            }

            if (str_contains($recommendation, 'reject') || $status === 'rejected') {
                $statusBuckets['Rejected']++;
                continue;
            }

            if (str_contains($recommendation, 'recommend') || $status === 'recommended') {
                $statusBuckets['Recommended']++;
                continue;
            }

            $statusBuckets['Needs Review']++;
        }

        $dailyLabels = [];
        $dailyMap = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayKey = $day->toDateString();
            $dailyMap[$dayKey] = 0;
            $dailyLabels[] = $day->format('M d');
        }

        foreach ($recentCandidates as $candidate) {
            if (!$candidate->created_at) {
                continue;
            }

            $dayKey = Carbon::parse($candidate->created_at)->toDateString();
            if (array_key_exists($dayKey, $dailyMap)) {
                $dailyMap[$dayKey]++;
            }
        }

        $scoreBands = [
            '0-39' => 0,
            '40-59' => 0,
            '60-79' => 0,
            '80-100' => 0,
        ];

        foreach ($recentCandidates as $candidate) {
            $score = (int) ($candidate->match_score ?? 0);

            if ($score >= 80) {
                $scoreBands['80-100']++;
            } elseif ($score >= 60) {
                $scoreBands['60-79']++;
            } elseif ($score >= 40) {
                $scoreBands['40-59']++;
            } else {
                $scoreBands['0-39']++;
            }
        }

        $topJobs = (clone $jobBaseQuery)
            ->withCount('candidates')
            ->orderByDesc('candidates_count')
            ->limit(5)
            ->get(['id', 'title']);

        $dashboardMetrics = [
            'jobs' => $jobCount,
            'uploads' => $screenedCount,
            'shortlisted' => $shortlistedCount,
            'averageScore' => $averageScore,
        ];

        $dashboardCharts = [
            'status' => [
                'labels' => array_keys($statusBuckets),
                'values' => array_values($statusBuckets),
            ],
            'dailyUploads' => [
                'labels' => $dailyLabels,
                'values' => array_values($dailyMap),
            ],
            'scoreBands' => [
                'labels' => array_keys($scoreBands),
                'values' => array_values($scoreBands),
            ],
            'topJobs' => [
                'labels' => $topJobs->pluck('title')->toArray(),
                'values' => $topJobs->pluck('candidates_count')->toArray(),
            ],
        ];

        $roleLabels = [
            'admin' => 'Admin',
            'recruiter' => 'Recruiter',
        ];

        $roleDescriptions = [
            'admin' => 'Manage recruiters, monitor analytics, audit operations, and configure system-wide settings.',
            'recruiter' => 'Create jobs, upload resumes, screen candidates, and shortlist interview-ready talent.',
        ];

        $modules = $role === 'admin'
            ? [
                ['title' => 'Dashboard', 'route' => route('dashboard')],
                ['title' => 'Recruiter Management', 'route' => route('admin.recruiters')],
                ['title' => 'Global Job Oversight', 'route' => route('admin.jobs')],
                ['title' => 'Analytics', 'route' => route('admin.analytics')],
                ['title' => 'Reports', 'route' => route('admin.reports')],
                ['title' => 'Audit Logs', 'route' => route('admin.audit')],
                ['title' => 'API Usage', 'route' => route('admin.api')],
                ['title' => 'System Config', 'route' => route('admin.system')],
                ['title' => 'Settings', 'route' => route('settings')],
            ]
            : [
                ['title' => 'Dashboard', 'route' => route('dashboard')],
                ['title' => 'Ranked Candidates', 'route' => route('ranked.candidates')],
                ['title' => 'Settings', 'route' => route('settings')],
            ];

        $functionalRequirements = $role === 'admin'
            ? [
                ['code' => 'Accounts', 'title' => 'Recruiter account management', 'description' => 'Currently tracking ' . $recruiterCount . ' recruiter account(s) in the platform.'],
                ['code' => 'Oversight', 'title' => 'Operational oversight', 'description' => 'Global oversight currently covers ' . $jobCount . ' job posting(s) and ' . $screenedCount . ' screened candidate(s).'],
                ['code' => 'System', 'title' => 'Configuration and governance', 'description' => 'Admin modules now provide live analytics, reports, audit timeline, and system-level visibility.'],
                ['code' => 'Access', 'title' => 'Authentication and access control', 'description' => 'Shortlisted candidates to date: ' . $shortlistedCount . '. Continue enforcing secure auth and role-based access.'],
            ]
            : [
                ['code' => 'Vacancies', 'title' => 'Vacancy oversight', 'description' => 'Recruiter tracks open vacancies, pipeline volume, and current hiring demand.'],
                ['code' => 'Applicants', 'title' => 'Applicant review', 'description' => 'Recruiter reviews applicants, ranking outputs, and shortlist readiness for each role.'],
                ['code' => 'Scoring', 'title' => 'Extraction, anonymization, and scoring', 'description' => 'System extracts candidate data, masks identifiers, and computes fit scores against job criteria.'],
                ['code' => 'Review', 'title' => 'Screening and shortlisting', 'description' => 'Recruiter ranks candidates, filters results, adds notes, and progresses selected candidates to interview stages.'],
            ];

        return view('panel.dashboard', [
            'pageTitle' => $roleLabels[$role] ?? 'Panel',
            'pageHeading' => $roleLabels[$role] ?? 'Panel',
            'activeNav' => 'dashboard',
            'role' => $role,
            'roleLabel' => $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role)),
            'roleDescription' => $roleDescriptions[$role] ?? 'Role panel',
            'modules' => $modules,
            'functionalRequirements' => $functionalRequirements,
            'dashboardMetrics' => $dashboardMetrics,
            'dashboardCharts' => $dashboardCharts,
            'user' => $user,
        ]);
    }
}
