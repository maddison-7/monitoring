@extends('layouts.applicant')

@section('content')
@php
    $interviews = $dashboard['upcomingInterviews'] ?? [];
@endphp

<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Upcoming Interviews</h2>
                <p class="text-xs text-muted">Scheduled sessions</p>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($interviews as $iv)
                <div class="rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/20 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ $iv['job'] }}</div>
                            <div class="text-xs text-muted mt-0.5">📅 {{ $iv['scheduled_at'] }}</div>
                            <div class="text-xs text-muted mt-0.5">📍 {{ $iv['venue'] }}</div>
                        </div>
                        <span class="chip chip-blue flex-shrink-0">{{ strtoupper($iv['status'] ?? 'scheduled') }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a class="btn-primary text-xs inline-flex" href="{{ route('applicant.downloads.interview-invitation', $iv['id']) }}">Download Invitation</a>

                        @if(!empty($iv['can_respond']))
                            <form method="POST" action="{{ route('applicant.interviews.respond', $iv['id']) }}">
                                @csrf
                                <input type="hidden" name="response" value="accept" />
                                <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">Accept</button>
                            </form>

                            <form method="POST" action="{{ route('applicant.interviews.respond', $iv['id']) }}">
                                @csrf
                                <input type="hidden" name="response" value="reject" />
                                <button type="submit" class="nav-btn px-3 py-1 text-xs">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-muted text-sm">No interviews scheduled yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
