@extends('layouts.applicant')

@section('content')
@php
    $apps = $dashboard['myApplications'] ?? [];
    $statusColors = [
        'ai_processing' => 'chip-blue',
        'pending' => 'chip-amber',
        'reviewed' => 'chip-blue',
        'shortlisted' => 'chip-green',
        'rejected' => 'chip-red',
        'hired' => 'chip-green',
        'offer_sent' => 'chip-blue',
        'offer_declined' => 'chip-red',
        'onboarding_completed' => 'chip-green',
        'placed' => 'chip-green',
        'interview' => 'chip-blue',
    ];
@endphp

<div class="space-y-6">
    <section class="ap-card">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">My Applications</h2>
                <p class="text-xs text-muted mt-0.5">All jobs you have applied for</p>
            </div>
            <a href="{{ route('applicant.jobs') }}" class="btn-primary text-xs">+ Apply New</a>
        </div>
        <div class="overflow-x-auto">
            <table class="ap-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">Position</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Applied</th>
                        <th class="px-5 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apps as $i => $app)
                        @php $sc = $statusColors[strtolower($app['status'] ?? 'pending')] ?? 'chip-slate'; @endphp
                        <tr class="border-t border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-5 py-3 text-muted">{{ $i + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $app['job'] }}</td>
                            <td class="px-5 py-3"><span class="chip {{ $sc }}">{{ ucwords(str_replace('_', ' ', (string) ($app['status'] ?? 'pending'))) }}</span></td>
                            <td class="px-5 py-3 text-muted text-xs">{{ $app['applied_at'] }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a class="btn-ghost text-xs" href="{{ route('applicant.downloads.application-slip', $app['id']) }}">Download Slip</a>

                                    @if(($app['offer_status'] ?? '') === 'pending')
                                        <form method="POST" action="{{ route('applicant.applications.offer.respond', $app['id']) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="response" value="accept" />
                                            <button type="submit" class="btn-primary text-xs">Accept Offer</button>
                                        </form>

                                        <form method="POST" action="{{ route('applicant.applications.offer.respond', $app['id']) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="response" value="reject" />
                                            <button type="submit" class="btn-ghost text-xs">Reject Offer</button>
                                        </form>
                                    @endif
                                </div>

                                <div class="mt-2 text-[11px] text-muted">
                                    @if(!empty($app['offer_status']))
                                        Offer: {{ strtoupper(str_replace('_', ' ', $app['offer_status'])) }}
                                        @if(!empty($app['offer_sent_at']))
                                            | Sent: {{ $app['offer_sent_at'] }}
                                        @endif
                                    @endif
                                    @if(!empty($app['onboarding_status']))
                                        <span class="ml-2">Onboarding: {{ strtoupper(str_replace('_', ' ', $app['onboarding_status'])) }}</span>
                                    @endif
                                    @if(!empty($app['placement_status']))
                                        <span class="ml-2">Placement: {{ strtoupper(str_replace('_', ' ', $app['placement_status'])) }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-muted">
                                <p>You have not applied to any jobs yet.</p>
                                <a href="{{ route('applicant.jobs') }}" class="mt-3 btn-primary text-xs inline-flex">Browse Jobs</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
