@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <section class="card p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">{{ $badge }}</div>
                    <h2 class="mt-4 text-3xl sm:text-4xl font-bold text-text page-title">{{ $pageHeading }}</h2>
                    <p class="mt-3 text-muted leading-7">{{ $summary }}</p>
                </div>

                @if (($moduleKey ?? '') === 'reports')
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.reports.export.csv') }}" class="nav-btn h-10 px-4 text-sm font-semibold">Export CSV</a>
                        <a href="{{ route('admin.reports.export.pdf') }}" class="nav-btn h-10 px-4 text-sm font-semibold">Export PDF</a>
                    </div>
                @endif

                @if (($moduleKey ?? '') === 'audit')
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.audit.export.csv') }}" class="nav-btn h-10 px-4 text-sm font-semibold">Export CSV</a>
                        <a href="{{ route('admin.audit.export.pdf') }}" class="nav-btn h-10 px-4 text-sm font-semibold">Export PDF</a>
                    </div>
                @endif
            </div>
        </section>

        @if (!empty($stats))
            <section>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($stats as $stat)
                        <article class="card p-5">
                            <div class="text-xs uppercase tracking-[0.16em] text-muted">{{ $stat['label'] }}</div>
                            <div class="mt-2 text-2xl font-bold text-text">{{ $stat['value'] }}</div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (!empty($tableColumns) && !empty($tableRows))
            <section class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text">Live Data</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                @foreach ($tableColumns as $column)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-muted">{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tableRows as $row)
                                <tr class="border-t border-border">
                                    @foreach ($tableColumns as $column)
                                        <td class="px-6 py-4 text-text">{{ $row[$column['key']] ?? 'N/A' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if (!empty($timeline))
            <section class="card p-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <h3 class="text-lg font-semibold text-text">Recent Activity Timeline</h3>
                    <span class="text-sm text-muted">Newest first</span>
                </div>

                <div class="space-y-3">
                    @foreach ($timeline as $event)
                        <article class="rounded-2xl border border-border px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-semibold text-text">{{ $event['event'] }}</div>
                                <div class="text-xs text-muted">{{ $event['time'] }}</div>
                            </div>
                            <p class="mt-1 text-sm text-muted">{{ $event['details'] }}</p>
                            <div class="mt-2 text-xs uppercase tracking-[0.14em] text-muted">Actor: {{ $event['actor'] }}</div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($moduleKey ?? '') === 'recruiters')
            @php
                $recruiterRecords = $moduleData['recruiterRecords'] ?? [];
                $applicantRecords = $moduleData['applicantRecords'] ?? [];
            @endphp

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Create Recruiter</h3>
                    <p class="mt-2 text-sm text-muted">Provision recruiter access with identity and login credentials.</p>

                    <form method="POST" action="{{ route('admin.recruiters.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <input name="first_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="First name" required />
                            <input name="last_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Last name" required />
                        </div>
                        <input type="email" name="email" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Email" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <input type="password" name="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Temporary password" required />
                            <input type="password" name="password_confirmation" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Confirm password" required />
                        </div>
                        <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">Create recruiter</button>
                    </form>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Manage Recruiters</h3>
                    <p class="mt-2 text-sm text-muted">Update identity, reset temporary password, or revoke account access.</p>

                    <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                        @forelse ($recruiterRecords as $recruiter)
                            <div class="rounded-2xl border border-border p-4 space-y-3">
                                <div class="flex items-center gap-3 pb-3 border-b border-border">
                                    <div class="h-10 w-10 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold flex-shrink-0 overflow-hidden">
                                        @if ($recruiter['profile_picture'] ?? false)
                                            <img src="{{ asset('storage/' . $recruiter['profile_picture']) }}" class="h-full w-full object-cover" alt="Profile picture" />
                                        @else
                                            {{ substr($recruiter['first_name'] ?? '', 0, 1) . substr($recruiter['last_name'] ?? '', 0, 1) }}
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-text">{{ $recruiter['name'] }}</div>
                                        <div class="text-xs text-muted">Jobs: {{ $recruiter['jobs'] }} | Screened: {{ $recruiter['screenings'] }}</div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.recruiters.update', $recruiter['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="first_name" value="{{ $recruiter['first_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="First name" required />
                                    <input name="last_name" value="{{ $recruiter['last_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Last name" required />
                                    <input type="email" name="email" value="{{ $recruiter['email'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Email" required />
                                    <div class="md:col-span-3">
                                        <button type="submit" class="nav-btn h-10 px-4 text-sm font-semibold">Update profile</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.recruiters.password', $recruiter['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="password" name="temporary_password" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Temporary password" required />
                                    <input type="password" name="temporary_password_confirmation" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Confirm password" required />
                                    <div>
                                        <button type="submit" class="nav-btn h-10 w-full px-4 text-sm font-semibold">Reset password</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.recruiters.delete', $recruiter['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50">Remove recruiter</button>
                                </form>

                                <form method="POST" action="{{ route('admin.users.impersonate', $recruiter['id']) }}">
                                    @csrf
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-indigo-200 text-indigo-700 text-sm font-semibold hover:bg-indigo-50">Enter account</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-muted">No recruiter accounts found yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Create Applicant</h3>
                    <p class="mt-2 text-sm text-muted">Provision applicant access with identity and login credentials.</p>

                    <form method="POST" action="{{ route('admin.applicants.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <input name="first_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="First name" required />
                            <input name="last_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Last name" required />
                        </div>
                        <input type="email" name="email" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Email" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <input type="password" name="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Temporary password" required />
                            <input type="password" name="password_confirmation" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="Confirm password" required />
                        </div>
                        <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">Create applicant</button>
                    </form>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Manage Applicants</h3>
                    <p class="mt-2 text-sm text-muted">Update identity, reset temporary password, or revoke account access.</p>

                    <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                        @forelse ($applicantRecords as $applicant)
                            <div class="rounded-2xl border border-border p-4 space-y-3">
                                <div class="flex items-center gap-3 pb-3 border-b border-border">
                                    <div class="h-10 w-10 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold flex-shrink-0 overflow-hidden">
                                        @if ($applicant['profile_picture'] ?? false)
                                            <img src="{{ asset('storage/' . $applicant['profile_picture']) }}" class="h-full w-full object-cover" alt="Profile picture" />
                                        @else
                                            {{ substr($applicant['first_name'] ?? '', 0, 1) . substr($applicant['last_name'] ?? '', 0, 1) }}
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-text">{{ $applicant['name'] }}</div>
                                        <div class="text-xs text-muted">Applications: {{ $applicant['applications'] }} | Interviews: {{ $applicant['interviews'] }}</div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.applicants.update', $applicant['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="first_name" value="{{ $applicant['first_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="First name" required />
                                    <input name="last_name" value="{{ $applicant['last_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Last name" required />
                                    <input type="email" name="email" value="{{ $applicant['email'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Email" required />
                                    <div class="md:col-span-3">
                                        <button type="submit" class="nav-btn h-10 px-4 text-sm font-semibold">Update profile</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.applicants.password', $applicant['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="password" name="temporary_password" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Temporary password" required />
                                    <input type="password" name="temporary_password_confirmation" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="Confirm password" required />
                                    <div>
                                        <button type="submit" class="nav-btn h-10 w-full px-4 text-sm font-semibold">Reset password</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.applicants.delete', $applicant['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50">Remove applicant</button>
                                </form>

                                <form method="POST" action="{{ route('admin.users.impersonate', $applicant['id']) }}">
                                    @csrf
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-indigo-200 text-indigo-700 text-sm font-semibold hover:bg-indigo-50">Enter account</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-muted">No applicant accounts found yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        @if (($moduleKey ?? '') === 'system')
            @php
                $systemSettings = $moduleData['systemSettings'] ?? [];
            @endphp

            <section class="card p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-text">System Defaults</h3>
                    <span class="text-sm text-muted">Persisted organization settings</span>
                </div>

                <form method="POST" action="{{ route('admin.system.update') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Skills weight (%)</label>
                            <input type="number" name="scoring_skills_weight" min="0" max="100" value="{{ old('scoring_skills_weight', $systemSettings['scoring_skills_weight'] ?? 50) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Experience weight (%)</label>
                            <input type="number" name="scoring_experience_weight" min="0" max="100" value="{{ old('scoring_experience_weight', $systemSettings['scoring_experience_weight'] ?? 30) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Education weight (%)</label>
                            <input type="number" name="scoring_education_weight" min="0" max="100" value="{{ old('scoring_education_weight', $systemSettings['scoring_education_weight'] ?? 20) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                    </div>

                    <input type="hidden" name="scoring_domain_weighting_enabled" value="0" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Shortlist threshold (%)</label>
                            <input type="number" name="shortlist_threshold" min="0" max="100" value="{{ old('shortlist_threshold', $systemSettings['shortlist_threshold'] ?? 75) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Session timeout (minutes)</label>
                            <input type="number" name="session_timeout_minutes" min="5" max="1440" value="{{ old('session_timeout_minutes', $systemSettings['session_timeout_minutes'] ?? 120) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Max resumes per upload</label>
                            <input type="number" name="resume_max_files" min="1" max="25" value="{{ old('resume_max_files', $systemSettings['resume_max_files'] ?? 10) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Max file size (MB)</label>
                            <input type="number" name="resume_max_file_size_mb" min="1" max="25" value="{{ old('resume_max_file_size_mb', $systemSettings['resume_max_file_size_mb'] ?? 5) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Allowed resume types</label>
                            <input type="text" name="allowed_resume_types" value="{{ old('allowed_resume_types', $systemSettings['allowed_resume_types'] ?? 'pdf,doc,docx') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="pdf,doc,docx" required />
                        </div>
                    </div>

                    <p class="text-xs text-muted">Note: Skills + Experience + Education weights must total 100.</p>

                    <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">Save system configuration</button>
                </form>
            </section>
        @endif

        @if (($moduleKey ?? '') === 'governance')
            @php
                $stalePublishedJobs = $moduleData['stalePublishedJobs'] ?? [];
                $purgeableClosedJobs = $moduleData['purgeableClosedJobs'] ?? [];
                $staleInterviews = $moduleData['staleInterviews'] ?? [];
            @endphp

            <section class="grid gap-6 xl:grid-cols-3">
                <article class="card p-6 xl:col-span-2">
                    <h3 class="text-lg font-semibold text-text">Governance Actions</h3>
                    <p class="mt-2 text-sm text-muted">Run these controls to keep system data aligned with the current recruitment flow.</p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-4">
                        <form method="POST" action="{{ route('admin.governance.sync-ai-statuses') }}">
                            @csrf
                            <button type="submit" class="nav-btn-primary h-11 w-full px-4 text-sm font-semibold">Sync AI Statuses</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.close-expired-jobs') }}">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">Close Expired Jobs</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.close-stale-interviews') }}">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">Cancel Stale Interviews</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.purge-closed-jobs') }}" onsubmit="return confirm('This will permanently delete closed jobs whose applications are fully finalized. Continue?');">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">Purge Closed Jobs</button>
                        </form>
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Feature Suggestions</h3>
                    <ul class="mt-3 space-y-2 text-sm text-muted">
                        <li>1. Daily scheduled governance jobs for auto-cleanup.</li>
                        <li>2. Approval workflow for mass status updates.</li>
                        <li>3. Recruiter SLA alerts for stale interviews and overdue decisions.</li>
                    </ul>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Expired Published Jobs</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($stalePublishedJobs as $job)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $job['title'] }}</div>
                                <div class="text-xs text-muted mt-1">Owner: {{ $job['owner'] }}</div>
                                <div class="text-xs text-muted mt-1">Deadline: {{ $job['deadline'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">No expired published jobs found.</div>
                        @endforelse
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Closed Jobs Ready For Purge</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($purgeableClosedJobs as $job)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $job['title'] }}</div>
                                <div class="text-xs text-muted mt-1">Owner: {{ $job['owner'] }}</div>
                                <div class="text-xs text-muted mt-1">Deadline: {{ $job['deadline'] }}</div>
                                <div class="text-xs text-muted mt-1">Applications: {{ $job['applications'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">No closed jobs are ready for purge.</div>
                        @endforelse
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">Stale Interviews</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($staleInterviews as $interview)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $interview['candidate'] }}</div>
                                <div class="text-xs text-muted mt-1">Job: {{ $interview['job'] }}</div>
                                <div class="text-xs text-muted mt-1">Scheduled: {{ $interview['scheduled_at'] }}</div>
                                <div class="text-xs text-muted mt-1">Status: {{ $interview['status'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">No stale interviews found.</div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        <section>
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-text">Admin Tasks</h3>
                <span class="text-sm text-muted">Operational notes for {{ $moduleKey ?? 'module' }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($cards as $card)
                    <article class="card p-5">
                        <h4 class="font-semibold text-text">{{ $card['title'] }}</h4>
                        <p class="mt-2 text-sm text-muted leading-6">{{ $card['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection