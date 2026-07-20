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
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ __('messages.interviews') }}</h2>
        <p class="text-sm text-muted mt-1">{{ __('messages.interviews_help') }}</p>
    </section>

    <section class="grid gap-4 xl:grid-cols-4">
        @foreach([
            ['label' => __('messages.scheduled'), 'value' => (int)($interviewStatus['scheduled'] ?? 0)],
            ['label' => __('messages.invitations'), 'value' => (int)($interviewStatus['invitation_sent'] ?? 0)],
            ['label' => __('messages.confirmed'), 'value' => (int)($interviewStatus['confirmed'] ?? 0)],
            ['label' => __('messages.completed'), 'value' => (int)($interviewStatus['completed'] ?? 0)],
        ] as $stat)
            <article class="rc-card p-4">
                <p class="text-xs uppercase tracking-[0.16em] text-muted">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($stat['value']) }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4">
        <article class="rc-card p-5">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ __('messages.todays_interviews') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($todayInterviews as $item)
                    <li class="rounded-xl border border-border dark:border-slate-700 px-3 py-3">
                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ $item['candidate'] }} - {{ $item['job'] }}</p>
                        <p class="text-xs text-muted mt-1">{{ __('messages.time') }}: {{ $item['scheduled_at'] }} | {{ __('messages.status') }}: {{ strtoupper($item['status']) }}</p>
                    </li>
                @empty
                    <li class="text-muted">{{ __('messages.no_interviews_today') }}</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="rc-card p-5">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ __('messages.upcoming_interviews') }}</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-muted text-xs uppercase tracking-[0.1em]">
                    <tr>
                        <th class="px-3 py-2 text-left">{{ __('messages.candidate') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.position') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.date_time') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.mode') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.venue_link') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.status') }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.update_result') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interviews as $item)
                        <tr class="border-t border-border dark:border-slate-700">
                            <td class="px-3 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $item['candidate'] }}</td>
                            <td class="px-3 py-3">{{ $item['job'] }}</td>
                            <td class="px-3 py-3 text-muted">{{ $item['scheduled_at'] }}</td>
                            <td class="px-3 py-3">{{ $item['mode'] }}</td>
                            <td class="px-3 py-3 text-muted">{{ $item['venue'] ?: __('messages.not_available') }}</td>
                            <td class="px-3 py-3">{{ strtoupper($item['status'] ?? 'scheduled') }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('hr.interviews.result', $item['id']) }}" class="grid gap-2 md:grid-cols-2">
                                    @csrf
                                    <select name="status" class="rounded-xl border border-border px-3 py-2 text-xs" required>
                                        <option value="confirmed">{{ __('messages.confirmed') }}</option>
                                        <option value="completed">{{ __('messages.completed') }}</option>
                                        <option value="cancelled">{{ __('messages.cancelled') }}</option>
                                        <option value="no_show">{{ __('messages.no_show') }}</option>
                                    </select>
                                    <select name="final_decision" class="rounded-xl border border-border px-3 py-2 text-xs">
                                        <option value="">{{ __('messages.no_final_decision') }}</option>
                                        <option value="review">{{ __('messages.review') }}</option>
                                        <option value="shortlisted">{{ __('messages.shortlisted') }}</option>
                                        <option value="hired">{{ __('messages.hired') }}</option>
                                        <option value="rejected">{{ __('messages.rejected') }}</option>
                                    </select>
                                    <input type="number" step="0.01" min="0" max="100" name="interview_score" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="{{ __('messages.interview_score') }}" />
                                    <input type="text" name="result_notes" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="{{ __('messages.result_notes') }}" />
                                    <div class="md:col-span-2">
                                        <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">{{ __('messages.save_result') }}</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-4 text-muted">{{ __('messages.no_upcoming_interviews') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
