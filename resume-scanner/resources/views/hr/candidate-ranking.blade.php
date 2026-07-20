@extends('layouts.recruiter')

@section('content')
@php $active = 'hr.candidate.ranking'; @endphp
<div class="card p-5 mb-6">
    <form method="GET" action="{{ route('hr.candidate.ranking') }}" class="grid gap-3 md:grid-cols-6">
        <select name="job_id" class="rounded-xl border border-border px-3 py-2">
            <option value="">{{ __('messages.all_jobs') }}</option>
            @foreach($jobs as $job)
                <option value="{{ $job->id }}" @selected((int)request('job_id', $selectedJobId) === (int)$job->id)>{{ $job->title }}</option>
            @endforeach
        </select>
        <input type="text" name="skill" value="{{ request('skill') }}" placeholder="{{ __('messages.skill_filter') }}" class="rounded-xl border border-border px-3 py-2" />
        <input type="text" name="degree" value="{{ request('degree') }}" placeholder="{{ __('messages.degree_filter') }}" class="rounded-xl border border-border px-3 py-2" />
        <input type="number" step="0.01" name="score_min" value="{{ request('score_min') }}" placeholder="{{ __('messages.min_score') }}" class="rounded-xl border border-border px-3 py-2" />
        <input type="number" min="1" name="top_n" value="{{ request('top_n', $topN > 0 ? $topN : ($selectedJobPositions > 0 ? $selectedJobPositions : '')) }}" placeholder="{{ __('messages.top_n_filter') }}" class="rounded-xl border border-border px-3 py-2" title="{{ __('messages.top_n_hint') }}" />
        <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">{{ __('messages.filter') }}</button>
    </form>
    @if($topN > 0)
        <p class="mt-3 text-xs text-muted">{{ __('messages.top_n_active_notice', ['count' => $topN]) }}</p>
    @endif
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left">{{ __('messages.rank') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.candidate') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.job') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.status') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.applied') }}</th>
                <th class="px-4 py-3 text-left">{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                @php
                    $applicant = $application->applicant;
                    $skills = collect($applicant?->skills ?? [])->pluck('name')->filter()->values();
                    $educationLevels = collect($applicant?->educations ?? [])->pluck('level')->filter()->values();
                    $hasCv = !empty($applicant?->cv_path);
                    $experienceYears = collect($applicant?->experiences ?? [])->sum(function ($experience) {
                        $start = $experience->start_date ? \Illuminate\Support\Carbon::parse($experience->start_date) : null;
                        $end = ($experience->is_current ?? false)
                            ? now()
                            : ($experience->end_date ? \Illuminate\Support\Carbon::parse($experience->end_date) : now());

                        if (!$start) {
                            return 0;
                        }

                        return max($start->floatDiffInYears($end), 0);
                    });
                    $cvTextPreview = trim((string) ($applicant?->cv_text ?? ''));
                    $rank = $applications->firstItem() + $loop->index;
                    $jobPositions = $topN > 0 ? $topN : (int) ($application->job?->positions ?? 0);
                    $isTopPick = $jobPositions > 0 && $rank <= $jobPositions;
                @endphp
                <tr class="border-t border-border {{ $isTopPick ? 'bg-emerald-50/60' : '' }}">
                    <td class="px-4 py-3 align-top">
                        <div class="font-semibold text-slate-900">#{{ $rank }}</div>
                        @if($isTopPick)
                            <span class="mt-1 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">{{ __('messages.top_pick_badge', ['count' => $jobPositions]) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold">{{ $application->applicant?->user?->name ?? __('messages.unknown') }}</div>
                        <div class="text-xs text-muted">{{ $application->applicant?->user?->email }}</div>
                        <div class="mt-2 text-xs text-muted space-y-1">
                            <div>{{ __('messages.phone') }}: {{ $applicant?->phone ?: __('messages.not_available') }}</div>
                            <div>{{ __('messages.location') }}: {{ trim(($applicant?->city ?: '') . ((($applicant?->city ?? '') && ($applicant?->country ?? '')) ? ', ' : '') . ($applicant?->country ?: '')) ?: __('messages.not_available') }}</div>
                            <div>{{ __('messages.experience') }}: {{ number_format($experienceYears, 1) }} {{ __('messages.years') }}</div>
                            <div>{{ __('messages.education') }}: {{ $educationLevels->take(2)->implode(', ') ?: __('messages.not_available') }}</div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse($skills->take(6) as $skill)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700">{{ $skill }}</span>
                            @empty
                                <span class="text-[11px] text-muted">{{ __('messages.no_skills_listed') }}</span>
                            @endforelse
                        </div>

                        @if($cvTextPreview !== '')
                            <details class="mt-2 text-xs text-slate-600">
                                <summary class="cursor-pointer font-medium text-slate-700">{{ __('messages.cv_extracted_summary') }}</summary>
                                <div class="mt-1 rounded-lg border border-border bg-slate-50 p-2 leading-relaxed">
                                    {{ \Illuminate\Support\Str::limit($cvTextPreview, 450) }}
                                </div>
                            </details>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $application->job?->title ?? __('messages.not_available') }}</td>
                    <td class="px-4 py-3">{{ strtoupper($application->status) }}</td>
                    <td class="px-4 py-3">{{ optional($application->applied_at)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <div class="space-y-3">
                            <a href="{{ route('hr.applications.ai-results', $application) }}" class="flex items-center justify-between rounded-2xl border border-border bg-slate-50 p-4 hover:bg-slate-100 transition">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.24em] text-slate-500">{{ __('messages.ai_score') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float)($application->aiScore->match_percentage ?? 0), 2) }}% &middot; {{ $application->aiScore->recommendation_level ?? __('messages.not_available') }}</p>
                                </div>
                                <span class="text-xs font-semibold text-blue-600">View AI Results &rarr;</span>
                            </a>

                            <div class="rounded-2xl border border-border bg-white p-4">
                                <div class="text-sm font-semibold text-slate-900">{{ __('messages.recruiter_actions') }}</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('hr.applications.shortlist', $application) }}">
                                        @csrf
                                        <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">{{ __('messages.shortlist') }}</button>
                                    </form>

                                    <form method="POST" action="{{ route('hr.applications.status', $application) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="status" value="review" />
                                        <button type="submit" class="nav-btn px-3 py-1 text-xs">{{ __('messages.mark_review') }}</button>
                                    </form>

                                    <form method="POST" action="{{ route('hr.applications.status', $application) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected" />
                                        <button type="submit" class="nav-btn px-3 py-1 text-xs">{{ __('messages.reject') }}</button>
                                    </form>
                                </div>

                                <details class="group mt-4 rounded-2xl border border-border bg-slate-50">
                                    <summary class="cursor-pointer px-4 py-3 flex items-center justify-between text-sm font-semibold text-slate-700">
                                        {{ __('messages.more_actions') }}
                                        <span class="text-slate-500 transition duration-150 group-open:-rotate-180">▼</span>
                                    </summary>
                                    <div class="p-4 grid gap-3">
                                        <div class="flex flex-wrap gap-2">
                                            @if($hasCv)
                                                <a href="{{ route('hr.applications.cv', ['application' => $application, 'disposition' => 'inline']) }}" target="_blank" class="nav-btn px-3 py-1 text-xs">{{ __('messages.preview_cv') }}</a>
                                                <a href="{{ route('hr.applications.cv', ['application' => $application, 'disposition' => 'download']) }}" class="nav-btn px-3 py-1 text-xs">{{ __('messages.download_cv') }}</a>
                                            @else
                                                <span class="text-xs text-amber-700">{{ __('messages.no_cv_uploaded') }}</span>
                                            @endif
                                        </div>

                                        <div class="grid gap-2 md:grid-cols-2">
                                            <form method="POST" action="{{ route('hr.applications.interviews.schedule', $application) }}" class="grid gap-2">
                                                @csrf
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <input type="datetime-local" name="scheduled_at" class="rounded-xl border border-border px-3 py-2 text-xs" required />
                                                    <select name="mode" class="rounded-xl border border-border px-3 py-2 text-xs" required>
                                                        <option value="online">{{ __('messages.online') }}</option>
                                                        <option value="physical">{{ __('messages.physical') }}</option>
                                                    </select>
                                                </div>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <input type="text" name="venue" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="{{ __('messages.venue_if_physical') }}" />
                                                    <input type="url" name="meeting_link" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="{{ __('messages.meeting_link_if_online') }}" />
                                                </div>
                                                <p class="text-xs text-muted">{{ __('messages.send_interview_invitation_email') }}</p>
                                                <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">{{ __('messages.schedule_interview') }}</button>
                                            </form>

                                            <form method="POST" action="{{ route('hr.applications.offer.send', $application) }}" class="grid gap-2">
                                                @csrf
                                                <input type="text" name="offer_message" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="{{ __('messages.offer_message_optional') }}" />
                                                <label class="inline-flex items-center gap-2 text-xs text-muted">
                                                    <input type="checkbox" name="send_email_offer" value="1" class="rounded border border-border" checked />
                                                    {{ __('messages.email_offer') }}
                                                </label>
                                                <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">{{ __('messages.send_offer') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-muted">{{ __('messages.no_candidates_found_filters') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $applications->links() }}</div>
@endsection
