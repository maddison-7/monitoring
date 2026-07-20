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
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ __('messages.notifications') }}</h2>
                <p class="text-sm text-muted mt-1">{{ __('messages.notifications_help') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($unread > 0)
                    <span class="rc-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
                <form method="POST" action="{{ route('hr.notifications.clear') }}" onsubmit="return confirm('Clear all read notifications? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="rounded-lg border border-border dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Clear Notifications</button>
                </form>
            </div>
        </div>
    </section>

    <section class="rc-card p-5">
        <ul class="space-y-2.5 text-sm">
            @forelse($notifications as $n)
                <li class="rounded-xl border border-border dark:border-slate-700 px-3 py-3">
                    <p class="font-medium text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        {{ $n['title'] }}
                        @if(!($n['read'] ?? false))
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        @endif
                    </p>
                    <p class="text-xs text-muted mt-1">{{ $n['message'] }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ $n['at'] }}</p>
                </li>
            @empty
                <li class="text-muted">{{ __('messages.no_notifications_short') }}</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
