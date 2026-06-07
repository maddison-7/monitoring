@extends('layouts.recruiter')
@php $active = 'dashboard'; @endphp

@section('content')
@php
    $metrics = $dashboard['metrics'] ?? [];
    $charts = $dashboard['charts'] ?? [];
    $totalApplicants = (int) ($metrics['Total Applicants'] ?? 0);
    $activeJobs = (int) ($metrics['Open Vacancies'] ?? 0);
    $shortlisted = (int) ($metrics['Shortlisted'] ?? 0);
    $interviews = (int) (($dashboard['interviewStatusOverview']['scheduled'] ?? 0) + ($dashboard['interviewStatusOverview']['invitation_sent'] ?? 0) + ($dashboard['interviewStatusOverview']['confirmed'] ?? 0) + ($dashboard['interviewStatusOverview']['completed'] ?? 0));
@endphp

<div class="mx-auto max-w-[1200px] space-y-5">
    <section class="rc-card overflow-hidden">
        <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 px-[28px] py-[30px] sm:px-[32px] sm:py-[34px] text-white">
            <h2 class="text-[1.55rem] sm:text-[1.9rem] leading-tight font-bold tracking-tight">Recruitment Control Center</h2>
            <p class="text-blue-100 text-[0.94rem] mt-1.5 max-w-3xl">Enterprise analytics for hiring operations, AI screening, candidate ranking, and interviews.</p>
            <div class="mt-5 flex flex-wrap items-center gap-2.5">
                <a href="{{ route('hr.jobs.create') }}" class="inline-flex h-[42px] w-[192px] items-center justify-center rounded-xl px-4 text-sm font-semibold bg-white text-blue-700 hover:bg-blue-50 transition-colors">Create Vacancy</a>
                <a href="{{ route('hr.reports.export.csv') }}" class="inline-flex h-[42px] w-[192px] items-center justify-center rounded-xl px-4 text-sm font-semibold bg-blue-500/20 border border-blue-200/30 text-white hover:bg-blue-500/30 transition-colors">Export CSV</a>
                <a href="{{ route('hr.reports.export.excel') }}" class="inline-flex h-[42px] w-[192px] items-center justify-center rounded-xl px-4 text-sm font-semibold bg-blue-500/20 border border-blue-200/30 text-white hover:bg-blue-500/30 transition-colors">Export Excel</a>
                <a href="{{ route('hr.reports.export.pdf') }}" class="inline-flex h-[42px] w-[192px] items-center justify-center rounded-xl px-4 text-sm font-semibold bg-blue-500/20 border border-blue-200/30 text-white hover:bg-blue-500/30 transition-colors">Export PDF</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rc-card p-5 h-[156px] flex flex-col justify-between">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Total Applicants</p>
            <p class="mt-2 text-4xl font-bold leading-none text-slate-800 dark:text-slate-100">{{ number_format($totalApplicants) }}</p>
        </article>
        <article class="rc-card p-5 h-[156px] flex flex-col justify-between">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Active Jobs</p>
            <p class="mt-2 text-4xl font-bold leading-none text-slate-800 dark:text-slate-100">{{ number_format($activeJobs) }}</p>
        </article>
        <article class="rc-card p-5 h-[156px] flex flex-col justify-between">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Shortlisted</p>
            <p class="mt-2 text-4xl font-bold leading-none text-slate-800 dark:text-slate-100">{{ number_format($shortlisted) }}</p>
        </article>
        <article class="rc-card p-5 h-[156px] flex flex-col justify-between">
            <p class="text-xs uppercase tracking-[0.16em] text-muted">Interviews</p>
            <p class="mt-2 text-4xl font-bold leading-none text-slate-800 dark:text-slate-100">{{ number_format($interviews) }}</p>
        </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
        <article class="rc-card p-5 xl:col-span-2">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Applications Per Vacancy</h3>
            <p class="text-xs text-muted mt-1">Bar chart of applicant volume across open roles.</p>
            <div class="mt-4 h-[320px]">
                <canvas id="hrDashboardBarChart"></canvas>
            </div>
        </article>

        <article class="rc-card p-5">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Ranking Distribution</h3>
            <p class="text-xs text-muted mt-1">Pie chart of candidate score buckets.</p>
            <div class="mt-4 h-[320px]">
                <canvas id="hrDashboardPieChart"></canvas>
            </div>
        </article>
    </section>

    <section class="rc-card p-5">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Recruitment Data Table</h3>
        <p class="text-xs text-muted mt-1">Quick tabular view for jobs and applicant counts.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-muted text-xs uppercase tracking-[0.1em]">
                    <tr>
                        <th class="px-3 py-2 text-left">Vacancy</th>
                        <th class="px-3 py-2 text-left">Applicants</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($charts['applicationsPerJob']['labels'] ?? []) as $idx => $label)
                        <tr class="border-t border-border dark:border-slate-700">
                            <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ $label }}</td>
                            <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ (int) (($charts['applicationsPerJob']['values'][$idx] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-muted">No chart data available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script id="hrDashboardChartsPayload" type="application/json">@json($charts)</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const chartPayload = JSON.parse(document.getElementById('hrDashboardChartsPayload')?.textContent || '{}');
    const isDark = document.documentElement.classList.contains('dark');

    const labels = chartPayload.applicationsPerJob?.labels || [];
    const values = chartPayload.applicationsPerJob?.values || [];

    const pieLabels = chartPayload.aiRankingDistribution?.labels || [];
    const pieValues = chartPayload.aiRankingDistribution?.values || [];

    const textColor = isDark ? '#CBD5E1' : '#334155';
    const gridColor = isDark ? 'rgba(148,163,184,0.2)' : 'rgba(148,163,184,0.28)';

    const barEl = document.getElementById('hrDashboardBarChart');
    if (barEl && labels.length) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Applicants',
                    data: values,
                    borderRadius: 8,
                    backgroundColor: '#2563EB'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
    }

    const pieEl = document.getElementById('hrDashboardPieChart');
    if (pieEl && pieLabels.length) {
        new Chart(pieEl, {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: ['#1D4ED8', '#0EA5E9', '#F59E0B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: textColor }
                    }
                }
            }
        });
    }
})();
</script>
@endsection
