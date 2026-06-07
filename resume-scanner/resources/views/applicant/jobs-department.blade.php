@extends('layouts.applicant')
@php $activeNav = 'applicant.jobs'; @endphp

@section('content')
<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Department Vacancies</p>
                <h1 class="mt-2 text-2xl font-bold text-text">{{ $department->name }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted">Browse the vacancies currently available in this department, review all job details here, and apply directly.</p>
            </div>
            <div class="rounded-2xl bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-200">
                <span class="font-semibold">{{ $jobs->total() }}</span> available job{{ $jobs->total() !== 1 ? 's' : '' }}
            </div>
        </div>
    </section>

    <section class="ap-card p-5">
        <form method="GET" action="{{ route('applicant.jobs.department', $department) }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search by title, qualification, or description" class="w-full rounded-xl border border-border bg-white px-3 py-2 text-sm text-text" />
            <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">Search Jobs</button>
        </form>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-200">Department: {{ $department->name }}</span>
            <a href="{{ route('applicant.jobs') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">Back to departments</a>
        </div>
    </section>

    <div class="space-y-4">
        @forelse($jobs as $job)
            <article class="ap-card p-5">
                <div class="flex flex-col gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold text-text">{{ $job->title }}</h2>
                            @if(in_array($job->id, $appliedJobIds, true))
                                <span class="chip chip-green">Already Applied</span>
                            @else
                                <span class="chip chip-blue">Open</span>
                            @endif
                        </div>

                        <p class="mt-4 text-sm leading-6 text-muted">{{ $job->description }}</p>

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Qualification</dt>
                                <dd class="mt-1 font-medium text-text">{{ $job->qualifications ?: 'N/A' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Experience</dt>
                                <dd class="mt-1 font-medium text-text">{{ ucfirst($job->experience_level ?: 'n/a') }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Positions</dt>
                                <dd class="mt-1 font-medium text-text">{{ $job->positions }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Deadline</dt>
                                <dd class="mt-1 font-medium text-text">{{ optional($job->application_deadline)->format('Y-m-d H:i') ?: 'N/A' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-4 py-4 dark:bg-slate-800/60">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Duties and Responsibilities</h3>
                                @php
                                    $dutiesText = trim((string) ($job->duties ?? ''));
                                    $normalizedDuties = preg_replace('/\s+(?=(?:[ivxlcdm]+|\d+)\.)/iu', "\n", $dutiesText) ?? $dutiesText;
                                    $dutiesItems = collect(preg_split('/\r\n|\r|\n/', $normalizedDuties) ?: [])
                                        ->map(fn (string $item): string => trim($item))
                                        ->filter(fn (string $item): bool => $item !== '')
                                        ->values();
                                @endphp

                                @if($dutiesItems->isNotEmpty())
                                    <ol class="mt-3 space-y-3 text-sm leading-7 text-text">
                                        @foreach($dutiesItems as $dutyItem)
                                            <li class="flex gap-3">
                                                <span class="font-semibold text-slate-500 dark:text-slate-300">{{ preg_match('/^((?:[ivxlcdm]+|\d+)\.)/iu', $dutyItem, $matches) ? $matches[1] : ($loop->iteration . '.') }}</span>
                                                <span>{{ preg_replace('/^((?:[ivxlcdm]+|\d+)\.)\s*/iu', '', $dutyItem) }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="mt-2 text-sm leading-6 text-text">No duties and responsibilities listed.</p>
                                @endif
                            </div>

                            <div class="rounded-xl bg-slate-50 px-4 py-4 dark:bg-slate-800/60">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Required Skills</h3>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse((array)($job->required_skills_json ?? []) as $skill)
                                        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-200">{{ $skill }}</span>
                                    @empty
                                        <span class="text-sm text-muted">No specific required skills listed.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <a href="{{ route('applicant.jobs.apply', $job) }}" class="nav-btn nav-btn-primary px-4 py-2 text-sm text-center">Apply Now</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="ap-card p-8 text-center text-muted">
                No vacancies are currently open in {{ $department->name }}.
            </div>
        @endforelse
    </div>

    <div>
        {{ $jobs->links() }}
    </div>
</div>
@endsection
