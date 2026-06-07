@extends('layouts.applicant')

@section('content')
@php
    $apps = $dashboard['myApplications'] ?? [];
    $interviews = $dashboard['upcomingInterviews'] ?? [];
    $latestApp = collect($apps)->first();
    $latestIv = collect($interviews)->first();
    $hiredApp = collect($apps)->first(function ($app) {
        $status = strtolower((string) ($app['status'] ?? ''));
        $offerStatus = strtolower((string) ($app['offer_status'] ?? ''));

        return in_array($status, ['hired', 'offer_sent', 'onboarding_completed', 'placed'], true)
            || in_array($offerStatus, ['pending', 'accepted'], true);
    });
@endphp

<div class="space-y-6">
    <section class="ap-card p-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-8 w-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center text-teal-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-8m0 8l-3-3m3 3l3-3"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 20H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2h-2"/></svg>
            </div>
            <div>
                <h2 class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Download Center</h2>
                <p class="text-xs text-muted">Your official documents</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center gap-3 rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">Application Slip</div>
                    <div class="text-xs text-muted">Proof of application</div>
                </div>
                @if($latestApp)
                    <a href="{{ route('applicant.downloads.application-slip', $latestApp['id']) }}" class="btn-primary text-xs">Download</a>
                @else
                    <span class="chip chip-slate text-xs">N/A</span>
                @endif
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                <div class="h-10 w-10 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">Interview Letter</div>
                    <div class="text-xs text-muted">Official interview invitation</div>
                </div>
                @if($latestIv)
                    <a href="{{ route('applicant.downloads.interview-invitation', $latestIv['id']) }}" class="btn-primary text-xs">Download</a>
                @else
                    <span class="chip chip-slate text-xs">N/A</span>
                @endif
            </div>

            <div class="flex items-center gap-3 rounded-xl border border-slate-100 dark:border-slate-700 p-4">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-slate-700 dark:text-slate-200">Offer Letter</div>
                    <div class="text-xs text-muted">Employment offer document</div>
                </div>
                @if($hiredApp)
                    <a href="{{ route('applicant.downloads.offer-letter', $hiredApp['id']) }}" class="btn-primary text-xs">Download</a>
                @else
                    <span class="chip chip-slate text-xs">N/A</span>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
