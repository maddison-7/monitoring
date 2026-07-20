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
                        <a href="{{ route('admin.reports.export.csv') }}" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.export_csv') }}</a>
                        <a href="{{ route('admin.reports.export.pdf') }}" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.export_pdf') }}</a>
                    </div>
                @endif

                @if (($moduleKey ?? '') === 'audit')
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.audit.export.csv') }}" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.export_csv') }}</a>
                        <a href="{{ route('admin.audit.export.pdf') }}" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.export_pdf') }}</a>
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
                    <h3 class="text-lg font-semibold text-text">{{ ($moduleKey ?? '') === 'audit' ? __('messages.login_history') : __('messages.live_data') }}</h3>
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
                                        <td class="px-6 py-4 text-text">{{ $row[$column['key']] ?? __('messages.not_available') }}</td>
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
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.recent_activity_timeline') }}</h3>
                    <span class="text-sm text-muted">{{ __('messages.newest_first') }}</span>
                </div>

                <div class="space-y-3">
                    @foreach ($timeline as $event)
                        <article class="rounded-2xl border border-border px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-semibold text-text">{{ $event['event'] }}</div>
                                <div class="text-xs text-muted">{{ $event['time'] }}</div>
                            </div>
                            <p class="mt-1 text-sm text-muted">{{ $event['details'] }}</p>
                            <div class="mt-2 text-xs uppercase tracking-[0.14em] text-muted">{{ __('messages.actor') }}: {{ $event['actor'] }}</div>
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
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.create_recruiter') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('messages.create_recruiter_help') }}</p>

                    <form method="POST" action="{{ route('admin.recruiters.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <input name="first_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.first_name_placeholder') }}" required />
                            <input name="last_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.last_name_placeholder') }}" required />
                        </div>
                        <input type="email" name="email" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.email_placeholder') }}" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <input type="password" name="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.temporary_password_placeholder') }}" required />
                            <input type="password" name="password_confirmation" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.confirm_password_placeholder') }}" required />
                        </div>
                        <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">{{ __('messages.create_recruiter_btn') }}</button>
                    </form>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.manage_recruiters') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('messages.manage_recruiters_help') }}</p>

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
                                        <div class="text-xs text-muted">{{ __('messages.jobs_label') }}: {{ $recruiter['jobs'] }} | {{ __('messages.screened_label') }}: {{ $recruiter['screenings'] }}</div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.recruiters.update', $recruiter['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="first_name" value="{{ $recruiter['first_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.first_name_placeholder') }}" required />
                                    <input name="last_name" value="{{ $recruiter['last_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.last_name_placeholder') }}" required />
                                    <input type="email" name="email" value="{{ $recruiter['email'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.email_placeholder') }}" required />
                                    <div class="md:col-span-3">
                                        <button type="submit" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.update_profile_btn') }}</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.recruiters.password', $recruiter['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="password" name="temporary_password" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.temporary_password_placeholder') }}" required />
                                    <input type="password" name="temporary_password_confirmation" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.confirm_password_placeholder') }}" required />
                                    <div>
                                        <button type="submit" class="nav-btn h-10 w-full px-4 text-sm font-semibold">{{ __('messages.reset_password_btn') }}</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.recruiters.delete', $recruiter['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50">{{ __('messages.remove_recruiter') }}</button>
                                </form>

                                <form method="POST" action="{{ route('admin.users.impersonate', $recruiter['id']) }}">
                                    @csrf
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-indigo-200 text-indigo-700 text-sm font-semibold hover:bg-indigo-50">{{ __('messages.enter_account') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-muted">{{ __('messages.no_recruiter_accounts') }}</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.create_applicant') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('messages.create_applicant_help') }}</p>

                    <form method="POST" action="{{ route('admin.applicants.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <input name="first_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.first_name_placeholder') }}" required />
                            <input name="last_name" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.last_name_placeholder') }}" required />
                        </div>
                        <input type="email" name="email" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.email_placeholder') }}" required />
                        <div class="grid gap-4 md:grid-cols-2">
                            <input type="password" name="password" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.temporary_password_placeholder') }}" required />
                            <input type="password" name="password_confirmation" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="{{ __('messages.confirm_password_placeholder') }}" required />
                        </div>
                        <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">{{ __('messages.create_applicant_btn') }}</button>
                    </form>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.manage_applicants') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('messages.manage_applicants_help') }}</p>

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
                                        <div class="text-xs text-muted">{{ __('messages.applications_label') }}: {{ $applicant['applications'] }} | {{ __('messages.interviews_label') }}: {{ $applicant['interviews'] }}</div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.applicants.update', $applicant['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="first_name" value="{{ $applicant['first_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.first_name_placeholder') }}" required />
                                    <input name="last_name" value="{{ $applicant['last_name'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.last_name_placeholder') }}" required />
                                    <input type="email" name="email" value="{{ $applicant['email'] }}" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.email_placeholder') }}" required />
                                    <div class="md:col-span-3">
                                        <button type="submit" class="nav-btn h-10 px-4 text-sm font-semibold">{{ __('messages.update_profile_btn') }}</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.applicants.password', $applicant['id']) }}" class="grid gap-3 md:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="password" name="temporary_password" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.temporary_password_placeholder') }}" required />
                                    <input type="password" name="temporary_password_confirmation" class="rounded-xl border border-border bg-white px-3 py-2 text-sm" placeholder="{{ __('messages.confirm_password_placeholder') }}" required />
                                    <div>
                                        <button type="submit" class="nav-btn h-10 w-full px-4 text-sm font-semibold">{{ __('messages.reset_password_btn') }}</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.applicants.delete', $applicant['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50">{{ __('messages.remove_applicant') }}</button>
                                </form>

                                <form method="POST" action="{{ route('admin.users.impersonate', $applicant['id']) }}">
                                    @csrf
                                    <button type="submit" class="h-10 px-4 rounded-xl border border-indigo-200 text-indigo-700 text-sm font-semibold hover:bg-indigo-50">{{ __('messages.enter_account') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-muted">{{ __('messages.no_applicant_accounts') }}</p>
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
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.system_defaults') }}</h3>
                    <span class="text-sm text-muted">{{ __('messages.persisted_org_settings') }}</span>
                </div>

                <form method="POST" action="{{ route('admin.system.update') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.skills_weight') }}</label>
                            <input type="number" name="scoring_skills_weight" min="0" max="100" value="{{ old('scoring_skills_weight', $systemSettings['scoring_skills_weight'] ?? 50) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.experience_weight') }}</label>
                            <input type="number" name="scoring_experience_weight" min="0" max="100" value="{{ old('scoring_experience_weight', $systemSettings['scoring_experience_weight'] ?? 30) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.education_weight') }}</label>
                            <input type="number" name="scoring_education_weight" min="0" max="100" value="{{ old('scoring_education_weight', $systemSettings['scoring_education_weight'] ?? 20) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                    </div>

                    <input type="hidden" name="scoring_domain_weighting_enabled" value="0" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.shortlist_threshold') }}</label>
                            <input type="number" name="shortlist_threshold" min="0" max="100" value="{{ old('shortlist_threshold', $systemSettings['shortlist_threshold'] ?? 75) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.session_timeout') }}</label>
                            <input type="number" name="session_timeout_minutes" min="5" max="1440" value="{{ old('session_timeout_minutes', $systemSettings['session_timeout_minutes'] ?? 120) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.max_resumes_per_upload') }}</label>
                            <input type="number" name="resume_max_files" min="1" max="25" value="{{ old('resume_max_files', $systemSettings['resume_max_files'] ?? 10) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.max_file_size_mb') }}</label>
                            <input type="number" name="resume_max_file_size_mb" min="1" max="25" value="{{ old('resume_max_file_size_mb', $systemSettings['resume_max_file_size_mb'] ?? 5) }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">{{ __('messages.allowed_resume_types') }}</label>
                            <input type="text" name="allowed_resume_types" value="{{ old('allowed_resume_types', $systemSettings['allowed_resume_types'] ?? 'pdf,doc,docx') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm" placeholder="pdf,doc,docx" required />
                        </div>
                    </div>

                    <p class="text-xs text-muted">{{ __('messages.weights_total_note') }}</p>

                    <button type="submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold">{{ __('messages.save_system_configuration') }}</button>
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
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.governance_actions') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('messages.governance_actions_help') }}</p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-4">
                        <form method="POST" action="{{ route('admin.governance.sync-ai-statuses') }}">
                            @csrf
                            <button type="submit" class="nav-btn-primary h-11 w-full px-4 text-sm font-semibold">{{ __('messages.sync_ai_statuses') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.close-expired-jobs') }}">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">{{ __('messages.close_expired_jobs') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.close-stale-interviews') }}">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">{{ __('messages.cancel_stale_interviews') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.governance.purge-closed-jobs') }}" onsubmit="return confirm(@json(__('messages.purge_closed_jobs_confirm')));">
                            @csrf
                            <button type="submit" class="nav-btn h-11 w-full px-4 text-sm font-semibold">{{ __('messages.purge_closed_jobs') }}</button>
                        </form>
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.feature_suggestions') }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-muted">
                        <li>1. {{ __('messages.feature_suggestion_1') }}</li>
                        <li>2. {{ __('messages.feature_suggestion_2') }}</li>
                        <li>3. {{ __('messages.feature_suggestion_3') }}</li>
                    </ul>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.expired_published_jobs') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($stalePublishedJobs as $job)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $job['title'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.owner_label') }}: {{ $job['owner'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.deadline_label') }}: {{ $job['deadline'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">{{ __('messages.no_expired_published_jobs') }}</div>
                        @endforelse
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.closed_jobs_ready_purge') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($purgeableClosedJobs as $job)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $job['title'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.owner_label') }}: {{ $job['owner'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.deadline_label') }}: {{ $job['deadline'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.applications_label') }}: {{ $job['applications'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">{{ __('messages.no_closed_jobs_purge') }}</div>
                        @endforelse
                    </div>
                </article>

                <article class="card p-6">
                    <h3 class="text-lg font-semibold text-text">{{ __('messages.stale_interviews') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        @forelse ($staleInterviews as $interview)
                            <div class="rounded-2xl border border-border px-4 py-3">
                                <div class="font-semibold text-text">{{ $interview['candidate'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.job_label') }}: {{ $interview['job'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.scheduled_label') }}: {{ $interview['scheduled_at'] }}</div>
                                <div class="text-xs text-muted mt-1">{{ __('messages.status_label') }}: {{ $interview['status'] }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-muted">{{ __('messages.no_stale_interviews') }}</div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        <section>
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-text">{{ __('messages.admin_tasks') }}</h3>
                <span class="text-sm text-muted">{{ __('messages.operational_notes_for', ['module' => $moduleKey ?? 'module']) }}</span>
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