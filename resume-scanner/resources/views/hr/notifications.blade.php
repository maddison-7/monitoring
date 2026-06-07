@extends('layouts.recruiter')
@php $active = 'notifications'; @endphp

@section('content')
@php
    $notifications = $dashboard['notifications'] ?? [];
    $unread = (int) ($dashboard['unreadNotifications'] ?? 0);
@endphp

<div class="space-y-6">
    <section class="rc-card p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Notifications</h2>
                <p class="text-sm text-muted mt-1">Recruitment alerts and system updates.</p>
            </div>
            @if($unread > 0)
                <span class="rc-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </div>
    </section>

    <section class="rc-card p-5">
        <ul class="space-y-2.5 text-sm">
            @forelse($notifications as $n)
                <li class="rounded-xl border border-border dark:border-slate-700 px-3 py-3">
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $n['title'] }}</p>
                    <p class="text-xs text-muted mt-1">{{ $n['message'] }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ $n['at'] }}</p>
                </li>
            @empty
                <li class="text-muted">No notifications.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
