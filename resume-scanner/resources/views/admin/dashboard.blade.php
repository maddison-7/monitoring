@extends('layouts.admin')
@php $activeNav = 'dashboard'; @endphp

@section('content')
@php
    $systemMetrics = $dashboard['systemMetrics'] ?? [];
    $systemHealth = $dashboard['systemHealth'] ?? [];
@endphp

<div class="space-y-6">
    <section class="admin-card overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-800 px-6 py-6 text-white">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]">System Controller</div>
                    <h2 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight">Admin Panel</h2>
                    <p class="mt-3 text-blue-100 leading-7">The highest control center of the recruitment platform. Monitor users, recruiters, departments, security, analytics, reports, and system health from one corporate dashboard.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($systemMetrics as $metric)
            <article class="admin-card admin-stat p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-adminMuted">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-slate-100">{{ $metric['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
        @foreach($systemHealth as $health)
            <article class="admin-card p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-adminMuted">{{ $health['label'] }}</p>
                <div class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $health['tone'] }}">{{ $health['value'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="admin-card p-5" id="analytics">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Recruitment Analytics</h3>
                <p class="text-sm text-adminMuted">Applications per department, trends, qualification statistics, and AI ranking distributions.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.export.csv') }}" class="admin-btn">CSV</a>
                <a href="{{ route('admin.reports.export.excel') }}" class="admin-btn">Excel</a>
                <a href="{{ route('admin.reports.export.pdf') }}" class="admin-btn">PDF</a>
            </div>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <div class="admin-card p-4"><p class="text-sm font-semibold mb-3 text-slate-800 dark:text-slate-100">Applications by Department</p><div class="h-64"><canvas id="deptApplicationsChart"></canvas></div></div>
            <div class="admin-card p-4"><p class="text-sm font-semibold mb-3 text-slate-800 dark:text-slate-100">Recruitment Trends</p><div class="h-64"><canvas id="trendChart"></canvas></div></div>
            <div class="admin-card p-4"><p class="text-sm font-semibold mb-3 text-slate-800 dark:text-slate-100">Qualification Statistics</p><div class="h-64"><canvas id="qualificationChart"></canvas></div></div>
            <div class="admin-card p-4"><p class="text-sm font-semibold mb-3 text-slate-800 dark:text-slate-100">AI Match Distribution</p><div class="h-64"><canvas id="aiChart"></canvas></div></div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script id="adminDashboardData" type="application/json">@json($dashboard)</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const data = JSON.parse(document.getElementById('adminDashboardData')?.textContent || '{}');
    const textColor = document.documentElement.classList.contains('dark') ? '#CBD5E1' : '#334155';
    const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,0.18)' : 'rgba(148,163,184,0.28)';

    const options = (extra = {}) => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: textColor } } },
        scales: {
            x: { ticks: { color: textColor }, grid: { color: gridColor } },
            y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
        },
        ...extra
    });

    new Chart(document.getElementById('deptApplicationsChart'), {
        type: 'bar',
        data: {
            labels: data?.charts?.applicationsPerDepartment?.labels || [],
            datasets: [{ label: 'Applications', data: data?.charts?.applicationsPerDepartment?.values || [], backgroundColor: '#2563EB', borderRadius: 8 }]
        },
        options: options({ plugins: { legend: { display: false } } })
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: data?.charts?.recruitmentTrends?.labels || [],
            datasets: [{ label: 'Applications', data: data?.charts?.recruitmentTrends?.values || [], borderColor: '#14B8A6', backgroundColor: 'rgba(20,184,166,0.16)', fill: true, tension: 0.35 }]
        },
        options: options()
    });

    new Chart(document.getElementById('qualificationChart'), {
        type: 'doughnut',
        data: {
            labels: data?.charts?.qualificationDistribution?.labels || [],
            datasets: [{ data: data?.charts?.qualificationDistribution?.values || [], backgroundColor: ['#2563EB', '#F59E0B', '#8B5CF6', '#94A3B8'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } } }
    });

    new Chart(document.getElementById('aiChart'), {
        type: 'pie',
        data: {
            labels: data?.charts?.aiRankingDistribution?.labels || [],
            datasets: [{ data: data?.charts?.aiRankingDistribution?.values || [], backgroundColor: ['#10B981', '#2563EB', '#94A3B8'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } } }
    });
})();
</script>
@endsection
