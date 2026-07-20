@extends('layouts.applicant')

@section('content')
@php
    $notifications = $dashboard['notifications'] ?? [];
@endphp

<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Notifications</h2>
                    <p class="text-xs text-muted">Your recent updates</p>
                </div>
            </div>
            <form method="POST" action="{{ route('applicant.notifications.clear') }}" onsubmit="return confirm('Clear all read notifications? This cannot be undone.');">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Clear Notifications</button>
            </form>
        </div>

        <ul class="space-y-2.5">
            @forelse($notifications as $notif)
                <li class="flex gap-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 px-3 py-2.5">
                    <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 flex-shrink-0 mt-0.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.16V11a6 6 0 10-12 0v3.16a2 2 0 01-.6 1.43L4 17h5"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-2">
                            {{ $notif['title'] }}
                            @if(!($notif['read'] ?? false))
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            @endif
                        </div>
                        <div class="text-xs text-muted">{{ $notif['message'] }}</div>
                        <div class="text-xs text-muted mt-0.5">{{ $notif['at'] }}</div>
                    </div>
                </li>
            @empty
                <li class="py-6 text-center text-muted text-sm">No notifications yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
