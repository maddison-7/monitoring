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
            'pageTitle' => 'Admin Panel',
            'pageHeading' => 'System Administrator Dashboard',
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
        $totalUsers = User::query()->count();
        $totalRecruiters = User::query()->where('role', 'recruiter')->count();
        $totalApplicants = User::query()->where('role', 'applicant')->count();
        $totalJobs = JobPosting::query()->count();
        $totalApplications = Application::query()->count();

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

        $qualificationRows = Education::query()->get(['level']);
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

        $aiScoreRows = AiScore::query()->pluck('match_percentage');
        $aiRankBuckets = [
            '90%+' => 0,
            '70-89%' => 0,
            'Below 70%' => 0,
        ];

        foreach ($aiScoreRows as $score) {
            $value = (float) $score;
            if ($value >= 90) {
                $aiRankBuckets['90%+']++;
            } elseif ($value >= 70) {
                $aiRankBuckets['70-89%']++;
            } else {
                $aiRankBuckets['Below 70%']++;
            }
        }

        $departmentRows = Department::query()
            ->leftJoin('job_postings', 'job_postings.department', '=', 'departments.name')
            ->leftJoin('applications', 'applications.job_id', '=', 'job_postings.id')
            ->selectRaw('departments.name as department, COUNT(DISTINCT applications.id) as applications_total, COUNT(DISTINCT job_postings.id) as jobs_total')
            ->groupBy('departments.name')
            ->orderByDesc('applications_total')
            ->limit(8)
            ->get();

        $vacancyOversight = JobPosting::query()
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

        $recentApplications = Application::query()
            ->with(['applicant.user', 'job'])
            ->latest('applied_at')
            ->limit(6)
            ->get()
            ->map(fn (Application $application): array => [
                'event' => (string) ($application->applicant?->user?->name ?? 'Unknown Applicant') . ' applied for ' . (string) ($application->job?->title ?? 'Unknown Job'),
                'time' => optional($application->applied_at)->diffForHumans(),
                'details' => 'Application ID ' . (string) ($application->application_id ?? $application->id),
                'actor' => 'Applicant Portal',
            ])
            ->all();

        $recentAudits = AuditLog::query()
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

        $todayInterviews = Interview::query()
            ->with(['application.applicant.user', 'application.job'])
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $interviewStatusRows = Interview::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $interviewStatusMap = [
            'scheduled' => 0,
            'invitation_sent' => 0,
            'confirmed' => 0,
            'completed' => 0,
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

        $notificationRows = Notification::query()->latest()->limit(6)->get();

        $securityLogs = LoginHistory::query()
            ->with('user:id,name,role')
            ->latest('logged_in_at')
            ->limit(6)
            ->get();

        $recentSystemLogs = $recentAudits;

        $systemSettings = AppSetting::query()->pluck('value', 'key');

        $storageTotal = @disk_total_space(storage_path()) ?: 0;
        $storageFree = @disk_free_space(storage_path()) ?: 0;
        $storageUsed = $storageTotal > 0 ? round((1 - ($storageFree / $storageTotal)) * 100, 1) : 0;

        $systemHealth = [
            ['label' => 'Server Status', 'value' => 'Online', 'tone' => 'bg-emerald-100 text-emerald-700'],
            ['label' => 'Database', 'value' => 'Connected', 'tone' => 'bg-blue-100 text-blue-700'],
            ['label' => 'Evaluation Engine', 'value' => !empty($systemSettings['openai_api_key'] ?? null) ? 'Active' : 'Configured', 'tone' => 'bg-violet-100 text-violet-700'],
            ['label' => 'Storage', 'value' => $storageUsed . '% Used', 'tone' => 'bg-amber-100 text-amber-700'],
        ];

        $backupSummary = [
            'last_backup' => (string) (AppSetting::getValue('backup_last_run', 'Not yet recorded') ?? 'Not yet recorded'),
            'status' => (string) (AppSetting::getValue('backup_status', 'Healthy') ?? 'Healthy'),
            'storage' => $storageUsed,
        ];

        $systemMetrics = [
            ['label' => 'Total Users', 'value' => number_format($totalUsers)],
            ['label' => 'Active Jobs', 'value' => number_format($totalJobs)],
            ['label' => 'Applications', 'value' => number_format($totalApplications)],
            ['label' => 'Recruiters', 'value' => number_format($totalRecruiters)],
        ];

        $recruitmentWorkflow = [
            'Manage Recruiters',
            'Configure Departments',
            'Monitor Recruitment Activities',
            'Manage Security',
            'View Analytics',
            'Generate Reports',
            'Maintain System',
        ];

        return [
            'metrics' => [
                'Total Users' => $totalUsers,
                'Active Jobs' => $totalJobs,
                'Applications' => $totalApplications,
                'Recruiters' => $totalRecruiters,
            ],
            'systemMetrics' => $systemMetrics,
            'charts' => [
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
            ],
            'departments' => $departmentRows->map(fn ($row): array => [
                'department' => (string) $row->department,
                'applications' => (int) $row->applications_total,
                'jobs' => (int) $row->jobs_total,
            ])->all(),
            'vacancyOversight' => $vacancyOversight,
            'interviews' => $todayInterviews->map(fn (Interview $interview): array => [
                'candidate' => (string) ($interview->application?->applicant?->user?->name ?? 'Unknown Candidate'),
                'job' => (string) ($interview->application?->job?->title ?? 'Unknown Job'),
                'scheduled_at' => optional($interview->scheduled_at)->format('Y-m-d H:i'),
                'mode' => strtoupper((string) $interview->mode),
                'venue' => (string) ($interview->venue ?? 'N/A'),
                'status' => (string) ($interview->status ?? 'scheduled'),
            ])->all(),
            'interviewStatus' => $interviewStatusMap,
            'backupSummary' => $backupSummary,
            'notifications' => $notificationRows->map(fn (Notification $notification): array => [
                'title' => (string) $notification->title,
                'message' => (string) $notification->message,
                'time' => optional($notification->created_at)->diffForHumans(),
            ])->all(),
            'securityLogs' => $securityLogs->map(fn (LoginHistory $history): array => [
                'user' => (string) ($history->user?->name ?? 'Unknown User'),
                'role' => (string) ($history->user?->role ?? 'unknown'),
                'ip' => (string) ($history->ip_address ?? 'N/A'),
                'time' => optional($history->logged_in_at)->diffForHumans(),
            ])->all(),
            'auditLogs' => $recentSystemLogs,
            'workflow' => $recruitmentWorkflow,
            'systemHealth' => $systemHealth,
            'settings' => [
                'shortlist_threshold' => (int) ($systemSettings['shortlist_threshold'] ?? 75),
                'scoring_skills_weight' => (int) ($systemSettings['scoring_skills_weight'] ?? 50),
                'scoring_experience_weight' => (int) ($systemSettings['scoring_experience_weight'] ?? 30),
                'scoring_education_weight' => (int) ($systemSettings['scoring_education_weight'] ?? 20),
            ],
            'recentApplications' => Application::query()
                ->with(['applicant.user', 'job'])
                ->latest('applied_at')
                ->limit(8)
                ->get()
                ->map(fn (Application $application): array => [
                    'candidate' => (string) ($application->applicant?->user?->name ?? 'Unknown Applicant'),
                    'job' => (string) ($application->job?->title ?? 'Unknown Job'),
                    'time' => optional($application->applied_at)->diffForHumans(),
                ])
                ->all(),
        ];
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
