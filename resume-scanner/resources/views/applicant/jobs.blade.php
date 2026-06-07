@extends('layouts.applicant')
@php $activeNav = 'applicant.jobs'; @endphp

@section('content')
@php
    $departmentCategories = $departmentCategories ?? collect();
    $totalJobs = (int) ($totalJobs ?? 0);
@endphp
<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Browse Vacancies</p>
                <h1 class="mt-2 text-2xl font-bold text-text">Available jobs posted by recruiters</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted">Choose a department first, then open a new results page for the vacancies you want.</p>
            </div>
            <div class="rounded-2xl bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-200">
                <span class="font-semibold">{{ $totalJobs }}</span> available job{{ $totalJobs !== 1 ? 's' : '' }}
            </div>
        </div>
    </section>

    <section class="ap-card p-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
            <h2 class="text-center text-2xl font-bold text-text">JOB CATEGORIES</h2>

            <div class="mt-5">
                <div class="rounded-xl border border-border bg-slate-50 px-3 py-2 dark:bg-slate-900/40">
                    <input id="departmentCategorySearch" type="text" placeholder="Search categories..." class="w-full bg-transparent text-sm text-text outline-none placeholder:text-muted" />
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach($departmentCategories as $department)
                    <a
                        href="{{ route('applicant.jobs.department', $department) }}"
                        class="department-card flex items-center justify-between rounded-xl border border-border bg-white px-4 py-3 transition hover:border-blue-200 hover:bg-slate-50 dark:bg-slate-900/30 dark:hover:border-blue-800"
                        data-department-name="{{ strtolower($department->name) }}"
                    >
                        <span class="pr-3 text-sm font-medium text-text">{{ $department->name }}</span>
                        <span class="inline-flex min-h-7 min-w-7 items-center justify-center rounded-full bg-blue-800 px-2 text-xs font-bold text-white">{{ (int) ($department->available_jobs_count ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ap-card p-8 text-center">
        <h3 class="text-xl font-semibold text-text">Choose a department first</h3>
        <p class="mt-2 text-sm text-muted">When you click a department above, the vacancy results will open on a new page.</p>
    </section>
</div>

<script>
    document.getElementById('departmentCategorySearch')?.addEventListener('input', function (event) {
        const value = String(event.target.value || '').trim().toLowerCase();

        document.querySelectorAll('.department-card').forEach((card) => {
            const matches = !value || card.dataset.departmentName.includes(value);
            card.classList.toggle('hidden', !matches);
        });
    });
</script>
@endsection
