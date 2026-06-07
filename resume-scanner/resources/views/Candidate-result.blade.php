@extends('layouts.recruiter')

@section('content')
@php $active = 'ranked-candidates'; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2 space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Candidate Result</div>
                    <h2 class="mt-4 text-2xl font-bold text-text">{{ $displayName }}</h2>
                        <p class="mt-2 text-sm text-muted">Review AI screening findings and scoring details for this candidate.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('ranked.candidates', ['job_id' => $job?->id]) }}" class="inline-flex items-center rounded-lg border border-border bg-white px-4 py-2 text-sm font-semibold text-text hover:bg-slate-50 transition">Back to ranking</a>
                    @if ($cvUrl)
                        <a href="{{ $cvUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-accent bg-white px-4 py-2 text-sm font-semibold text-accent hover:bg-accent hover:text-white transition">Open CV</a>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-border bg-white p-4">
                    <div class="text-xs uppercase tracking-[0.14em] text-muted">Job</div>
                    <div class="mt-2 font-semibold text-text">{{ $job?->title ?? 'Unknown job' }}</div>
                    <div class="text-sm text-muted">{{ $job?->department ?? 'No department' }}</div>
                </div>
                <div class="rounded-2xl border border-border bg-white p-4">
                    <div class="text-xs uppercase tracking-[0.14em] text-muted">Recommendation</div>
                    <div class="mt-2 font-semibold text-text">{{ $screening['recommendation'] ?? $candidate->recommendation ?? 'Review' }}</div>
                    <div class="text-sm text-muted">Score {{ (int) ($screening['match_score'] ?? $candidate->match_score ?? 0) }}%</div>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-white p-4">
                <h3 class="font-semibold text-text">AI Issues / Findings</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-muted text-xs uppercase">Matched skills</div>
                        <div class="mt-1 text-text">{{ implode(', ', (array) ($screening['matched_skills'] ?? [])) ?: 'None' }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-muted text-xs uppercase">Missing skills</div>
                        <div class="mt-1 text-text">{{ implode(', ', (array) ($screening['missing_skills'] ?? [])) ?: 'None' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-white p-4">
                <h3 class="font-semibold text-text">Scoring Breakdown</h3>
                <div class="mt-3 space-y-2 text-sm text-muted">
                    <div class="flex justify-between"><span>Skills</span><span class="text-text">{{ (int) data_get($scoreBreakdown, 'skill_score', 0) }}</span></div>
                    <div class="flex justify-between"><span>Experience</span><span class="text-text">{{ (int) data_get($scoreBreakdown, 'experience_score', 0) }}</span></div>
                    <div class="flex justify-between"><span>Education</span><span class="text-text">{{ (int) data_get($scoreBreakdown, 'education_score', 0) }}</span></div>
                </div>
            </div>
        </section>

    </div>
@endsection