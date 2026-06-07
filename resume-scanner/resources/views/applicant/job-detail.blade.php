@extends('layouts.applicant')
@php $activeNav = 'applicant.jobs'; @endphp

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <h2 class="text-xl font-semibold text-text">{{ $job->title }}</h2>
        <p class="mt-2 text-sm text-muted">{{ $job->description }}</p>

        <dl class="mt-5 grid gap-3 sm:grid-cols-2 text-sm">
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Location</div><div class="font-medium">{{ $job->location ?: 'N/A' }}</div></div>
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Deadline</div><div class="font-medium">{{ optional($job->application_deadline)->format('Y-m-d H:i') }}</div></div>
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Experience Level</div><div class="font-medium">{{ ucfirst($job->experience_level ?? 'N/A') }}</div></div>
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Min Years</div><div class="font-medium">{{ $job->min_years_experience }}</div></div>
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Qualification</div><div class="font-medium">{{ $job->qualifications ?: 'N/A' }}</div></div>
            <div class="rounded-xl border border-border p-3"><div class="text-muted">Positions</div><div class="font-medium">{{ $job->positions }}</div></div>
        </dl>

        <div class="mt-5">
            <h3 class="font-semibold">Required Skills</h3>
            <div class="mt-2 flex flex-wrap gap-2">
                @forelse((array)($job->required_skills_json ?? []) as $skill)
                    <span class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent">{{ $skill }}</span>
                @empty
                    <span class="text-sm text-muted">No specific required skills listed.</span>
                @endforelse
            </div>
        </div>
    </section>

    <aside class="card p-5">
        <h3 class="font-semibold mb-3">Application</h3>
        <p class="text-sm text-muted">Review all requirements carefully before submitting your application.</p>

        <div class="mt-4 flex flex-col gap-2">
            <a href="{{ route('applicant.jobs') }}" class="nav-btn px-3 py-2 text-sm">Back to Jobs</a>
            <form method="POST" action="{{ route('applicant.jobs.save', $job) }}">
                @csrf
                <button type="submit" class="w-full nav-btn px-3 py-2 text-sm">{{ $isSaved ? 'Remove Saved Job' : 'Save Job' }}</button>
            </form>
            @if($alreadyApplied)
                <span class="inline-flex justify-center rounded-xl bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-700">Already Applied</span>
            @else
                <a href="{{ route('applicant.jobs.apply', $job) }}" class="nav-btn nav-btn-primary px-3 py-2 text-sm">Apply Online</a>
            @endif
        </div>
    </aside>
</div>
@endsection
