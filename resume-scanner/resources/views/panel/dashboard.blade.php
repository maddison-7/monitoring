@extends('layouts.panel')

@section('content')
    <div class="space-y-6">
        <section class="card p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Role workspace</div>
                    <h2 class="mt-4 text-3xl sm:text-4xl font-bold text-text page-title">{{ $roleLabel }} Panel</h2>
                    <p class="mt-3 text-muted leading-7">{{ $roleDescription }}</p>
                </div>

                <div class="rounded-2xl border border-border bg-slate-50 px-5 py-4">
                    <div class="text-xs uppercase tracking-[0.16em] text-muted">Current user</div>
                    <div class="mt-2 font-semibold text-text">{{ $user->name }}</div>
                    <div class="text-sm text-muted">{{ $user->email }}</div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-text">Live Insights</h3>
                <span class="text-sm text-muted">Auto-updated from current records</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="card p-5">
                    <div class="text-xs uppercase tracking-[0.16em] text-muted">Jobs</div>
                    <div class="mt-2 text-3xl font-bold text-text">{{ number_format((int) ($dashboardMetrics['jobs'] ?? 0)) }}</div>
                    <p class="mt-2 text-sm text-muted">Active job postings in scope</p>
                </article>

                <article class="card p-5">
                    <div class="text-xs uppercase tracking-[0.16em] text-muted">Uploads</div>
                    <div class="mt-2 text-3xl font-bold text-text">{{ number_format((int) ($dashboardMetrics['uploads'] ?? 0)) }}</div>
                    <p class="mt-2 text-sm text-muted">Total screened candidate files</p>
                </article>

                <article class="card p-5">
                    <div class="text-xs uppercase tracking-[0.16em] text-muted">Shortlisted</div>
                    <div class="mt-2 text-3xl font-bold text-text">{{ number_format((int) ($dashboardMetrics['shortlisted'] ?? 0)) }}</div>
                    <p class="mt-2 text-sm text-muted">Candidates moved to shortlist</p>
                </article>

                <article class="card p-5">
                    <div class="text-xs uppercase tracking-[0.16em] text-muted">Average Score</div>
                    <div class="mt-2 text-3xl font-bold text-text">{{ number_format((float) ($dashboardMetrics['averageScore'] ?? 0), 1) }}%</div>
                    <p class="mt-2 text-sm text-muted">Mean fit score for screened CVs</p>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="card p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="font-semibold text-text">Candidate Pipeline Status</h4>
                        <span class="text-xs text-muted">Last 14 days</span>
                    </div>
                    <div class="h-72">
                        <canvas id="statusChart"></canvas>
                    </div>
                </article>

                <article class="card p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="font-semibold text-text">Daily Resume Processing</h4>
                        <span class="text-xs text-muted">Uploads trend</span>
                    </div>
                    <div class="h-72">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="card p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="font-semibold text-text">Score Distribution</h4>
                        <span class="text-xs text-muted">Quality bands</span>
                    </div>
                    <div class="h-72">
                        <canvas id="scoreChart"></canvas>
                    </div>
                </article>

                <article class="card p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h4 class="font-semibold text-text">Top Job Pipelines</h4>
                        <span class="text-xs text-muted">Most candidate volume</span>
                    </div>
                    <div class="h-72">
                        <canvas id="jobsChart"></canvas>
                    </div>
                </article>
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-text">Core workflow</h3>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($functionalRequirements as $requirement)
                    <article class="card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent">{{ $requirement['code'] }}</div>
                        </div>
                        <h4 class="mt-4 font-semibold text-text">{{ $requirement['title'] }}</h4>
                        <p class="mt-2 text-sm text-muted leading-6">{{ $requirement['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

    </div>
@endsection

@section('scripts')
    <script id="dashboardChartsData" type="application/json">@json($dashboardCharts ?? [])</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            const palette = {
                blue: '#2563EB',
                indigo: '#4F46E5',
                teal: '#0F766E',
                amber: '#D97706',
                rose: '#BE123C',
                slate: '#64748B'
            };

            const chartsElement = document.getElementById('dashboardChartsData');
            const charts = chartsElement ? JSON.parse(chartsElement.textContent || '{}') : {};
            const statusLabels = charts.status?.labels ?? [];
            const statusValues = charts.status?.values ?? [];
            const dailyLabels = charts.dailyUploads?.labels ?? [];
            const dailyValues = charts.dailyUploads?.values ?? [];
            const scoreLabels = charts.scoreBands?.labels ?? [];
            const scoreValues = charts.scoreBands?.values ?? [];
            const jobLabels = charts.topJobs?.labels ?? [];
            const jobValues = charts.topJobs?.values ?? [];

            const textColor = document.documentElement.classList.contains('dark') ? '#CBD5E1' : '#334155';
            const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148, 163, 184, 0.22)' : 'rgba(148, 163, 184, 0.28)';

            const makeBaseOptions = (extra = {}) => ({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, precision: 0 },
                        grid: { color: gridColor }
                    }
                },
                ...extra
            });

            const statusEl = document.getElementById('statusChart');
            if (statusEl) {
                new Chart(statusEl, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusValues,
                            backgroundColor: [palette.teal, palette.blue, palette.amber, palette.rose],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor, font: { size: 12, weight: '600' } }
                            }
                        }
                    }
                });
            }

            const dailyEl = document.getElementById('dailyChart');
            if (dailyEl) {
                new Chart(dailyEl, {
                    type: 'line',
                    data: {
                        labels: dailyLabels,
                        datasets: [{
                            label: 'Resumes processed',
                            data: dailyValues,
                            borderColor: palette.blue,
                            backgroundColor: 'rgba(37, 99, 235, 0.18)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        }]
                    },
                    options: makeBaseOptions()
                });
            }

            const scoreEl = document.getElementById('scoreChart');
            if (scoreEl) {
                new Chart(scoreEl, {
                    type: 'bar',
                    data: {
                        labels: scoreLabels,
                        datasets: [{
                            label: 'Candidates',
                            data: scoreValues,
                            backgroundColor: [palette.rose, palette.amber, palette.indigo, palette.teal],
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: makeBaseOptions({
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    })
                });
            }

            const jobsEl = document.getElementById('jobsChart');
            if (jobsEl) {
                new Chart(jobsEl, {
                    type: 'bar',
                    data: {
                        labels: jobLabels,
                        datasets: [{
                            label: 'Candidates',
                            data: jobValues,
                            backgroundColor: palette.indigo,
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: makeBaseOptions({
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    })
                });
            }
        })();
    </script>
@endsection
