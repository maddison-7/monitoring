@extends('layouts.recruiter')
@php $active = 'analytics'; @endphp

@section('content')
@php
    $metrics = $dashboard['metrics'] ?? [];
    $charts = $dashboard['charts'] ?? [];
    $weeklyGrowth = (float) ($dashboard['weeklyGrowth'] ?? 0);
@endphp

<div class="space-y-6">
    <section class="rc-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Analytics & Reports</h2>
                <p class="text-sm text-muted mt-1">Recruitment insights, AI completion rates, and export-ready reports.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('hr.reports.export.csv') }}" class="rc-btn">Export CSV</a>
                <a href="{{ route('hr.reports.export.excel') }}" class="rc-btn">Export Excel</a>
                <a href="{{ route('hr.reports.export.pdf') }}" class="rc-btn">Export PDF</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rc-card p-4"><p class="text-xs uppercase tracking-[0.16em] text-muted">Total Applicants</p><p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format((int)($metrics['Total Applicants'] ?? 0)) }}</p></article>
        <article class="rc-card p-4"><p class="text-xs uppercase tracking-[0.16em] text-muted">Open Vacancies</p><p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format((int)($metrics['Open Vacancies'] ?? 0)) }}</p></article>
        <article class="rc-card p-4"><p class="text-xs uppercase tracking-[0.16em] text-muted">AI Completed</p><p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format((int)($metrics['AI Completed'] ?? 0)) }}</p></article>
        <article class="rc-card p-4"><p class="text-xs uppercase tracking-[0.16em] text-muted">Weekly Growth</p><p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($weeklyGrowth, 1) }}%</p></article>
    </section>

    <section class="rc-card p-5">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Analytics Summary</h3>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-border dark:border-slate-700 p-4">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Applications Per Vacancy</p>
                <ul class="mt-2 space-y-1 text-sm text-muted">
                    @foreach(($charts['applicationsPerJob']['labels'] ?? []) as $i => $label)
                        <li>{{ $label }}: {{ (int)(($charts['applicationsPerJob']['values'][$i] ?? 0)) }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-xl border border-border dark:border-slate-700 p-4">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">AI Ranking Distribution</p>
                <ul class="mt-2 space-y-1 text-sm text-muted">
                    @foreach(($charts['aiRankingDistribution']['labels'] ?? []) as $i => $label)
                        <li>{{ $label }}: {{ (int)(($charts['aiRankingDistribution']['values'][$i] ?? 0)) }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
</div>
@endsection
