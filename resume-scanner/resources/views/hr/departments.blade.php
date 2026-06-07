@extends('layouts.recruiter')
@php $active = 'departments'; @endphp

@section('content')
@php
    $departmentStats = $dashboard['departmentStats'] ?? [];
@endphp

<div class="space-y-6">
    <section class="rc-card p-5">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Departments</h2>
        <p class="text-sm text-muted mt-1">Department-level applicant distribution and workload snapshot.</p>
    </section>

    <section class="rc-card p-5">
        <ul class="space-y-2 text-sm">
            @forelse($departmentStats as $department)
                <li class="rounded-xl border border-border dark:border-slate-700 px-3 py-2 flex items-center justify-between">
                    <span class="text-slate-700 dark:text-slate-200">{{ $department['department'] }}</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format((int) $department['count']) }}</span>
                </li>
            @empty
                <li class="text-muted">No department data.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
