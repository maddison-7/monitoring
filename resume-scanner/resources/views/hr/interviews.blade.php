@extends('layouts.recruiter')
@php $active = 'interviews'; @endphp

@section('content')
@php
    $interviews = $dashboard['upcomingInterviews'] ?? [];
    $todayInterviews = $dashboard['todayInterviews'] ?? [];
    $interviewStatus = $dashboard['interviewStatusOverview'] ?? [];
@endphp

<div class="space-y-6">
    <section class="rc-card p-5">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Interviews</h2>
        <p class="text-sm text-muted mt-1">Schedule interviews, track confirmations, and monitor interview workflow.</p>
    </section>

    <section class="grid gap-4 xl:grid-cols-4">
        @foreach([
            ['label' => 'Scheduled', 'value' => (int)($interviewStatus['scheduled'] ?? 0)],
            ['label' => 'Invitations', 'value' => (int)($interviewStatus['invitation_sent'] ?? 0)],
            ['label' => 'Confirmed', 'value' => (int)($interviewStatus['confirmed'] ?? 0)],
            ['label' => 'Completed', 'value' => (int)($interviewStatus['completed'] ?? 0)],
        ] as $stat)
            <article class="rc-card p-4">
                <p class="text-xs uppercase tracking-[0.16em] text-muted">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($stat['value']) }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4">
        <article class="rc-card p-5">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Today's Interviews</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($todayInterviews as $item)
                    <li class="rounded-xl border border-border dark:border-slate-700 px-3 py-3">
                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ $item['candidate'] }} - {{ $item['job'] }}</p>
                        <p class="text-xs text-muted mt-1">Time: {{ $item['scheduled_at'] }} | Status: {{ strtoupper($item['status']) }}</p>
                    </li>
                @empty
                    <li class="text-muted">No interviews scheduled for today.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="rc-card p-5">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Upcoming Interviews</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-muted text-xs uppercase tracking-[0.1em]">
                    <tr>
                        <th class="px-3 py-2 text-left">Candidate</th>
                        <th class="px-3 py-2 text-left">Position</th>
                        <th class="px-3 py-2 text-left">Date & Time</th>
                        <th class="px-3 py-2 text-left">Mode</th>
                        <th class="px-3 py-2 text-left">Venue / Link</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Update Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interviews as $item)
                        <tr class="border-t border-border dark:border-slate-700">
                            <td class="px-3 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $item['candidate'] }}</td>
                            <td class="px-3 py-3">{{ $item['job'] }}</td>
                            <td class="px-3 py-3 text-muted">{{ $item['scheduled_at'] }}</td>
                            <td class="px-3 py-3">{{ $item['mode'] }}</td>
                            <td class="px-3 py-3 text-muted">{{ $item['venue'] ?: 'N/A' }}</td>
                            <td class="px-3 py-3">{{ strtoupper($item['status'] ?? 'scheduled') }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('hr.interviews.result', $item['id']) }}" class="grid gap-2 md:grid-cols-2">
                                    @csrf
                                    <select name="status" class="rounded-xl border border-border px-3 py-2 text-xs" required>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="no_show">No Show</option>
                                    </select>
                                    <select name="final_decision" class="rounded-xl border border-border px-3 py-2 text-xs">
                                        <option value="">No final decision</option>
                                        <option value="review">Review</option>
                                        <option value="shortlisted">Shortlisted</option>
                                        <option value="hired">Hired</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    <input type="number" step="0.01" min="0" max="100" name="interview_score" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="Interview score" />
                                    <input type="text" name="result_notes" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="Result notes" />
                                    <div class="md:col-span-2">
                                        <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">Save Result</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-4 text-muted">No upcoming interviews.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
