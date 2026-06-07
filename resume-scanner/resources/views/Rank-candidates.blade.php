@extends('layouts.recruiter')

@section('content')
@php $active = 'ranked-candidates'; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Ranking</div>
                    <h2 class="mt-4 text-2xl font-bold text-text">Ranked candidates</h2>
                    <p class="mt-2 text-sm text-muted">Candidates are sorted by suitability score and anonymized before review.</p>
                </div>
                @if ($selectedJob)
                <div class="flex gap-2">
                    <a href="{{ route('ranked.candidates.export.csv', ['job_id' => $selectedJob->id]) }}" class="inline-flex items-center rounded-lg border border-accent bg-white px-4 py-2 text-sm font-semibold text-accent hover:bg-accent hover:text-white transition">
                        Export CSV
                    </a>
                    <a href="{{ route('ranked.candidates.export.pdf', ['job_id' => $selectedJob->id]) }}" class="inline-flex items-center rounded-lg border border-accent bg-white px-4 py-2 text-sm font-semibold text-accent hover:bg-accent hover:text-white transition">
                        Export PDF
                    </a>
                </div>
                @endif
            </div>

            @if ($jobs->isEmpty())
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    No saved jobs yet. Create a job description, then upload CVs to see rankings.
                </div>
            @else
                <form method="GET" action="{{ route('ranked.candidates') }}" class="mt-5">
                    <label class="block text-sm font-medium text-text mb-2">Job position</label>
                    <select name="job_id" onchange="this.form.submit()" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent">
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}" @selected(optional($selectedJob)->id === $job->id)>
                                {{ $job->title }}@if(!empty($job->department)) - {{ $job->department }}@endif
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="mt-6 overflow-x-auto">
                <form id="bulk-delete-form" method="POST" action="{{ route('ranked.candidates.bulk-delete') }}" onsubmit="return confirm('Delete selected candidate results? This cannot be undone.');" class="mb-3">
                    @csrf
                    <input type="hidden" name="job_id" value="{{ optional($selectedJob)->id }}">
                    <button type="submit" class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Delete selected
                    </button>
                </form>
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.14em] text-muted">
                        <tr>
                            <th class="py-3 pr-4">
                                <input id="select-all-candidates" type="checkbox" class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
                            </th>
                            <th class="py-3 pr-4">Rank</th>
                            <th class="py-3 pr-4">Candidate</th>
                            <th class="py-3 pr-4">Date</th>
                            <th class="py-3 pr-4">Skills</th>
                            <th class="py-3 pr-4">Experience</th>
                            <th class="py-3 pr-4">Score</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidates as $index => $candidate)
                            @php
                                $skills = collect($candidate->skills_json ?? [])->take(4)->all();
                                $recommendation = $candidate->recommendation ?? 'Review';
                                $candidateName = (string) ($candidate->display_name ?? ('Candidate ' . ($index + 1)));
                                $screening = $candidate->parsed_json['screening'] ?? [];
                                $matchedSkills = $screening['matched_skills'] ?? [];
                                $missingSkills = $screening['missing_skills'] ?? [];
                                $breakdown = $screening['score_breakdown'] ?? [];
                                $tone = match ($recommendation) {
                                    'Shortlist' => 'bg-emerald-50 text-emerald-700',
                                    'Consider' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $score = (int) ($candidate->match_score ?? 0);
                                $barClass = match (true) {
                                    $score >= 90 => 'w-[90%]',
                                    $score >= 85 => 'w-[85%]',
                                    $score >= 75 => 'w-[75%]',
                                    $score >= 70 => 'w-[70%]',
                                    $score >= 60 => 'w-[60%]',
                                    $score >= 50 => 'w-[50%]',
                                    $score >= 40 => 'w-[40%]',
                                    default => 'w-[30%]',
                                };
                            @endphp
                            <tr class="border-t border-border">
                                <td class="py-4 pr-4">
                                    <input
                                        type="checkbox"
                                        name="candidate_ids[]"
                                        value="{{ $candidate->id }}"
                                        form="bulk-delete-form"
                                        class="candidate-select-checkbox h-4 w-4 rounded border-border text-accent focus:ring-accent"
                                    >
                                </td>
                                <td class="py-4 pr-4 font-semibold text-text">#{{ $index + 1 }}</td>
                                <td class="py-4 pr-4 text-text">
                                    {{ $candidateName }}
                                </td>
                                <td class="py-4 pr-4 text-muted">{{ optional($candidate->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-4 pr-4">
                                    <div class="flex flex-wrap gap-2 mb-1">
                                        @forelse ($candidate->parsed_json['skills'] ?? $skills as $skill)
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">{{ $skill }}</span>
                                        @empty
                                            <span class="text-xs text-muted">No extracted skills</span>
                                        @endforelse
                                    </div>
                                    @if (!empty($matchedSkills) || !empty($missingSkills))
                                        <div class="text-xs">
                                            <span class="text-emerald-600">Matched:</span>
                                            @foreach ($matchedSkills as $mskill)
                                                <span class="px-1">{{ $mskill }}</span>
                                            @endforeach
                                            <span class="text-rose-600 ml-2">Missing:</span>
                                            @foreach ($missingSkills as $mskill)
                                                <span class="px-1">{{ $mskill }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 pr-4 text-muted">{{ number_format((float) ($candidate->years_experience ?? 0), 1) }} years</td>
                                <td class="py-4 pr-4">
                                    <div class="flex items-center gap-3 mb-1">
                                        <div class="h-2 w-28 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-accent {{ $barClass }}"></div>
                                        </div>
                                        <span class="font-semibold text-text">{{ $score }}%</span>
                                    </div>
                                    @if (!empty($breakdown))
                                        <div class="text-xs text-muted">
                                            Skills: {{ $breakdown['skill_score'] ?? '-' }} | Exp: {{ $breakdown['experience_score'] ?? '-' }} | Edu: {{ $breakdown['education_score'] ?? '-' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4 pr-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $tone }}">{{ $recommendation }}</span>
                                </td>
                                <td class="py-4 pr-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('ranked.candidates.show', $candidate->id) }}" class="inline-flex rounded-lg border border-accent bg-white px-3 py-1 text-xs font-semibold text-accent hover:bg-accent hover:text-white transition">
                                            View
                                        </a>
                                        <form method="POST" action="{{ route('ranked.candidates.delete', $candidate->id) }}" onsubmit="return confirm('Delete this candidate result? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-border">
                                <td colspan="9" class="py-6 text-sm text-muted">No processed candidates yet for this job. Upload CVs to generate rankings.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-text">What it does</h3>
                <p class="mt-2 text-sm text-muted">Combines parsing, matching, scoring, ranking, and privacy-aware screening in one view.</p>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-text">Review flow</h3>
                <p class="mt-2 text-sm text-muted">Use shortlist candidates first, then compare middle-score profiles.</p>
            </div>
        </aside>
    </div>

    <script>
        (function () {
            const selectAll = document.getElementById('select-all-candidates');
            if (!selectAll) {
                return;
            }

            const itemSelector = '.candidate-select-checkbox';

            selectAll.addEventListener('change', function () {
                document.querySelectorAll(itemSelector).forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        })();
    </script>
@endsection