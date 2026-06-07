@extends('layouts.applicant')

@section('content')
@php
    $recommended = $dashboard['recommendedJobs'] ?? [];
    $recommendationsMeta = $dashboard['recommendationsMeta'] ?? [];
@endphp

<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-8 w-8 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.07 4.93A10 10 0 114.93 19.07"/></svg>
            </div>
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-slate-100 text-sm">AI Recommendations</h2>
                <p class="text-xs text-muted">Based on your skills and CV</p>
            </div>
        </div>

        <ul class="space-y-3">
            @forelse($recommended as $job)
                <li class="rounded-xl border border-slate-100 dark:border-slate-700 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr($job['title'], 0, 2)) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $job['title'] }}</div>
                            <div class="text-xs text-muted">{{ $job['location'] }}</div>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <span class="chip chip-green text-xs">{{ number_format((float) $job['score'], 0) }}% match</span>
                            <span class="chip text-xs {{ ($job['recommendation'] ?? '') === 'Strong Fit' ? 'chip-green' : (($job['recommendation'] ?? '') === 'Potential Fit' ? 'chip-blue' : 'chip-amber') }}">{{ $job['recommendation'] ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">{{ $job['clear_recommendation'] ?? 'AI recommendation unavailable.' }}</p>

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.14em] text-muted">Matched skills</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @forelse(($job['matched_skills'] ?? []) as $skill)
                                    <span class="chip chip-green text-[11px]">{{ $skill }}</span>
                                @empty
                                    <span class="text-xs text-muted">No direct matches detected.</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.14em] text-muted">Missing skills to improve</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @forelse(($job['missing_skills'] ?? []) as $skill)
                                    <span class="chip chip-amber text-[11px]">{{ $skill }}</span>
                                @empty
                                    <span class="text-xs text-muted">No major missing skills for this role.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-1.5 justify-end">
                        <a class="btn-primary text-xs px-2 py-1" href="{{ route('applicant.jobs.show', $job['id']) }}">View</a>
                        @if(!($job['already_applied'] ?? false))
                            <form method="POST" action="{{ route('applicant.jobs.save', $job['id']) }}">@csrf<button class="btn-ghost text-xs px-2 py-1">Save</button></form>
                        @else
                            <span class="chip chip-slate text-xs">Already applied</span>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-6 text-center text-muted text-sm">
                    @if((int)($recommendationsMeta['open_jobs_count'] ?? 0) === 0)
                        No published vacancies are open right now. Please check back later.
                    @else
                        Upload your CV, add skills, and complete profile details to get clear AI job recommendations.
                    @endif
                </li>
            @endforelse
        </ul>

        @if(($recommendationsMeta['mode'] ?? '') === 'applied_fallback')
            <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700 dark:border-blue-900/60 dark:bg-blue-900/20 dark:text-blue-200">
                You have already applied to current open vacancies. Showing best-fit guidance so you can track these roles and prepare for similar upcoming jobs.
            </div>
        @endif
    </section>
</div>
@endsection
