@extends('layouts.recruiter')

@section('content')
@php
    $active = 'hr.candidate.ranking';
    $applicant = $application->applicant;
    $aiScore = $application->aiScore;
    $matchedSkills = collect($aiScore->matched_skills ?? []);
    $missingSkills = collect($aiScore->missing_skills ?? []);
    $strengths = collect($aiScore->strengths ?? []);
    $weaknesses = collect($aiScore->weaknesses ?? []);
    $riskFactors = collect($aiScore->risk_factors ?? []);
    $hiringAdvantages = collect($aiScore->hiring_advantages ?? []);
    $finalDecisionStatuses = ['hired', 'placed', 'offer_declined', 'rejected'];
    $canRescan = !in_array((string) $application->status, $finalDecisionStatuses, true);
@endphp

<div class="mb-4">
    <a href="{{ route('hr.candidate.ranking') }}" class="text-xs text-muted hover:underline">&larr; {{ __('messages.applicants') }}</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-text">{{ $applicant?->user?->name ?? __('messages.unknown') }}</h2>
                <p class="text-sm text-muted mt-1">{{ __('messages.job') }}: {{ $application->job?->title ?? __('messages.not_available') }}</p>
            </div>
            <div class="text-right">
                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ __('messages.ai_score') }}</p>
                <p class="text-3xl font-bold text-slate-900">{{ number_format((float) ($aiScore->match_percentage ?? 0), 2) }}%</p>
                @if($canRescan)
                    <form method="POST" action="{{ route('hr.applications.rescan', $application) }}" class="mt-2" onsubmit="return confirm('Not satisfied with this AI result? This will re-run the AI scan and replace the current result. Continue?');">
                        @csrf
                        <button type="submit" class="nav-btn px-3 py-1 text-xs">&#x21bb; {{ __('messages.rescan_with_ai') }}</button>
                    </form>
                @else
                    <p class="mt-2 text-[11px] text-muted">Final decision made &mdash; re-scan unavailable.</p>
                @endif
            </div>
        </div>

        @if($aiScore)
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-border p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Skills Match</p>
                    <p class="mt-1 text-xl font-semibold">{{ $aiScore->skills_score !== null ? number_format((float) $aiScore->skills_score, 1) . '%' : __('messages.not_available') }}</p>
                </div>
                <div class="rounded-xl border border-border p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Experience Match</p>
                    <p class="mt-1 text-xl font-semibold">{{ $aiScore->experience_score !== null ? number_format((float) $aiScore->experience_score, 1) . '%' : __('messages.not_available') }}</p>
                </div>
                <div class="rounded-xl border border-border p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Education Match</p>
                    <p class="mt-1 text-xl font-semibold">{{ $aiScore->education_score !== null ? number_format((float) $aiScore->education_score, 1) . '%' : __('messages.not_available') }}</p>
                </div>
                <div class="rounded-xl border border-border p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">GPA Analysis</p>
                    <p class="mt-1 text-xl font-semibold">
                        {{ $aiScore->gpa_score !== null ? number_format((float) $aiScore->gpa_score, 1) . '%' : __('messages.not_available') }}
                    </p>
                    <p class="text-xs text-muted mt-1">Applicant GPA: {{ $applicant?->gpa ?? __('messages.not_available') }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-sm font-semibold text-slate-900">Skills Match Analysis</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse($matchedSkills as $skill)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700">{{ $skill }}</span>
                    @empty
                        <span class="text-xs text-muted">{{ __('messages.not_available') }}</span>
                    @endforelse
                </div>
                @if($missingSkills->isNotEmpty())
                    <p class="mt-2 text-xs font-medium text-slate-500">Missing Skills</p>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        @foreach($missingSkills as $skill)
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] text-red-700">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Strengths</p>
                    <ul class="mt-2 space-y-1 text-xs text-slate-600 list-disc list-inside">
                        @forelse($strengths as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li class="list-none text-muted">{{ __('messages.not_available') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Weaknesses</p>
                    <ul class="mt-2 space-y-1 text-xs text-slate-600 list-disc list-inside">
                        @forelse($weaknesses as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li class="list-none text-muted">{{ __('messages.not_available') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            @if($riskFactors->isNotEmpty() || $hiringAdvantages->isNotEmpty())
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Risk Factors</p>
                        <ul class="mt-2 space-y-1 text-xs text-slate-600 list-disc list-inside">
                            @foreach($riskFactors as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Hiring Advantages</p>
                        <ul class="mt-2 space-y-1 text-xs text-slate-600 list-disc list-inside">
                            @foreach($hiringAdvantages as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(!empty($aiScore->explanation))
                <div class="mt-6 rounded-xl border border-border bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">AI Reasoning</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $aiScore->explanation }}</p>
                </div>
            @elseif(!empty($aiScore->summary))
                <div class="mt-6 rounded-xl border border-border bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">AI Reasoning</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $aiScore->summary }}</p>
                </div>
            @endif
        @else
            <p class="mt-6 text-sm text-muted">AI scoring has not completed for this application yet.</p>
        @endif
    </section>

    <section class="card p-5">
        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Final Recommendation</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $aiScore->recommendation_level ?? __('messages.not_available') }}</p>

        <div class="mt-6 border-t border-border pt-4">
            <p class="text-sm font-semibold text-slate-900 mb-2">Recruiter Actions</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('hr.applications.shortlist', $application) }}">
                    @csrf
                    <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">{{ __('messages.shortlist') }}</button>
                </form>
                <form method="POST" action="{{ route('hr.applications.status', $application) }}">
                    @csrf
                    <input type="hidden" name="status" value="review" />
                    <button type="submit" class="nav-btn px-3 py-1 text-xs">{{ __('messages.mark_review') }}</button>
                </form>
                <form method="POST" action="{{ route('hr.applications.status', $application) }}">
                    @csrf
                    <input type="hidden" name="status" value="rejected" />
                    <button type="submit" class="nav-btn px-3 py-1 text-xs">{{ __('messages.reject') }}</button>
                </form>
            </div>
            <a href="{{ route('hr.candidate.ranking', ['job_id' => $application->job_id]) }}" class="mt-4 inline-block text-xs text-muted hover:underline">{{ __('messages.more_actions') }} &rarr;</a>
        </div>
    </section>
</div>
@endsection
