@extends('layouts.applicant')
@php $activeNav = 'dashboard'; @endphp

@section('content')
@php
    $stats = $dashboard['accountStats'] ?? [];
    $totalApps = (int) ($stats['total_applications'] ?? 0);
    $user = auth()->user();
    $firstName = $user->first_name ?? explode(' ', $user->name ?? 'Applicant')[0];
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-6 text-white shadow-lg">
        <div class="relative z-10">
            <p class="text-blue-200 text-sm font-medium mb-1">{{ now()->format('l, d F Y') }}</p>
            <h1 class="text-2xl sm:text-3xl font-bold">Welcome Back, {{ $firstName }} 👋</h1>
            <p class="mt-1 text-blue-200 text-sm max-w-2xl">Track your job applications and recruitment progress here. You have <span class="font-semibold text-white">{{ $totalApps }} application{{ $totalApps !== 1 ? 's' : '' }}</span> in progress.</p>
            <div class="mt-4">
                <a href="{{ route('applicant.jobs') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white text-blue-700 font-semibold text-sm px-4 py-2 hover:bg-blue-50 transition-colors">Browse Vacancies</a>
            </div>
        </div>
        <div class="absolute top-0 right-0 h-48 w-48 rounded-full bg-white/5 -translate-y-12 translate-x-12"></div>
        <div class="absolute bottom-0 right-16 h-32 w-32 rounded-full bg-white/5 translate-y-8"></div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center gap-2 border-b border-slate-200 px-6 py-4 dark:border-slate-700">
            <svg class="h-5 w-5 text-slate-600 dark:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m7.78-14.78-.7.7M4.92 19.08l-.7.7M21 12h-1M4 12H3m16.08 7.08-.7-.7M5.62 5.62l-.7-.7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15c0-1.2.7-2 2-2h2c1.3 0 2 .8 2 2"/>
                <circle cx="12" cy="9" r="2.4"/>
            </svg>
            <h2 class="text-xl font-bold tracking-wide text-slate-800 dark:text-slate-100">APPLICATION TIPS</h2>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2">
            <article class="rounded-xl bg-blue-100/80 p-5 dark:bg-blue-900/30">
                <div class="mb-3 flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-800 text-sm font-bold text-white">1</span>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Complete your Profile</h3>
                </div>
                <p class="text-xl leading-7 text-slate-700 dark:text-slate-200">Please complete ALL of the required fields of the forms found in the left-hand menu of this page.</p>
            </article>

            <article class="rounded-xl bg-blue-100/80 p-5 dark:bg-blue-900/30">
                <div class="mb-3 flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-800 text-sm font-bold text-white">2</span>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Apply to a vacancy online</h3>
                </div>
                <p class="text-xl leading-7 text-slate-700 dark:text-slate-200">Click on the vacancies tab at the top of the page. Select a vacancy of interest. Read the job requirements thoroughly. Click 'apply' for this vacancy.</p>
            </article>
        </div>

        <div class="px-6 pb-5 text-xl text-slate-700 dark:text-slate-200">
            If you are experiencing additional problems, please give us feedback
            <a href="{{ route('privacy') }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">here</a>.
        </div>
    </section>
</div>
@endsection
