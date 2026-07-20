<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle ?? 'Panel' }} - Resume Screener</title>

    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = savedTheme ? savedTheme === 'dark' : prefersDark;

            if (useDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        panel: '#F8FAFC',
                        surface: '#FFFFFF',
                        border: '#E2E8F0',
                        text: '#0F172A',
                        muted: '#64748B',
                        accent: '#2563EB',
                        accentSoft: '#DBEAFE',
                    },
                    boxShadow: {
                        soft: '0 18px 40px rgba(15, 23, 42, 0.06)'
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .8rem 1rem;
            border-radius: .9rem;
            color: #E2E8F0;
            transition: all .15s ease;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #FFFFFF;
        }

        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
            font-weight: 600;
        }

        .sidebar-icon {
            width: 1.05rem;
            height: 1.05rem;
            flex-shrink: 0;
        }

        .sidebar-label {
            white-space: nowrap;
        }

        #desktopSidebar {
            width: 18rem;
            min-width: 18rem;
            background: linear-gradient(180deg, #0F172A 0%, #1D4ED8 55%, #0F766E 100%);
            color: #E2E8F0;
        }

        @media (min-width: 1280px) {
            #desktopSidebar {
                width: 20rem;
                min-width: 20rem;
            }
        }

        #panelLayout.sidebar-collapsed #desktopSidebar {
            width: 5.5rem;
            min-width: 5.5rem;
            max-width: 5.5rem;
        }

        #panelLayout.sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding-left: .55rem;
            padding-right: .55rem;
        }

        #panelLayout.sidebar-collapsed .sidebar-label,
        #panelLayout.sidebar-collapsed .sidebar-brand-copy,
        #panelLayout.sidebar-collapsed .sidebar-user-info,
        #panelLayout.sidebar-collapsed #desktopSidebarTitle {
            display: none;
        }

        .card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .page-title {
            letter-spacing: -0.03em;
            color: #0F172A;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: .9rem;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            color: #0F172A;
            transition: all .15s ease;
        }

        .nav-btn:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }

        .nav-btn-primary {
            background: #2563EB;
            border-color: #2563EB;
            color: #FFFFFF;
        }

        .nav-btn-primary:hover {
            background: #1D4ED8;
            border-color: #1D4ED8;
        }

        .mobile-nav-link {
            display: block;
            padding: .72rem .9rem;
            border-radius: .8rem;
            color: #334155;
            font-weight: 500;
        }

        .mobile-nav-link:hover {
            background: #EFF6FF;
            color: #1D4ED8;
        }

        .mobile-nav-link.active {
            background: #DBEAFE;
            color: #1D4ED8;
            font-weight: 600;
        }

        html.dark body {
            background: #020617;
            color: #E2E8F0;
        }

        html.dark .card {
            background: #0F172A;
            border-color: #1E293B;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.42);
        }

        html.dark .sidebar-link {
            color: #CBD5E1;
        }

        html.dark .sidebar-link:hover,
        html.dark .sidebar-link.active {
            background: #1E293B;
            color: #93C5FD;
        }

        html.dark .nav-btn {
            background: #0F172A;
            border-color: #1E293B;
            color: #E2E8F0;
        }

        html.dark .nav-btn:hover {
            background: #1E293B;
            border-color: #334155;
        }

        html.dark .mobile-nav-link {
            color: #CBD5E1;
        }

        html.dark .mobile-nav-link:hover,
        html.dark .mobile-nav-link.active {
            background: #1E293B;
            color: #93C5FD;
        }

        html.dark aside,
        html.dark header {
            background: rgba(15, 23, 42, 0.92);
            border-color: #1E293B;
        }

        html.dark .border-border,
        html.dark .border,
        html.dark .border-t,
        html.dark .border-b,
        html.dark .border-r {
            border-color: #1E293B !important;
        }

        html.dark .bg-white,
        html.dark .bg-white\/90,
        html.dark .bg-slate-50,
        html.dark .bg-slate-100,
        html.dark .bg-panel,
        html.dark .bg-paper {
            background-color: #0F172A !important;
        }

        html.dark .text-text,
        html.dark .text-ink {
            color: #E2E8F0 !important;
        }

        html.dark .text-muted,
        html.dark .text-slate-600,
        html.dark .text-slate-700,
        html.dark .text-slateSoft {
            color: #94A3B8 !important;
        }

        html.dark input,
        html.dark select,
        html.dark textarea {
            background: #111827 !important;
            color: #E2E8F0 !important;
            border-color: #334155 !important;
        }

        html.dark .bg-accentSoft {
            background: #1E3A8A !important;
        }

        html.dark .text-accent,
        html.dark .text-blueDeep {
            color: #93C5FD !important;
        }
    </style>
</head>
<body class="h-full">
@php
    $activeNav = $activeNav ?? '';
    $user = auth()->user();
    $normalizedRole = strtolower(trim((string) ($user?->role ?? 'recruiter')));
    if (in_array($normalizedRole, ['hr_manager', 'hr-manager'], true)) {
        $normalizedRole = 'admin';
    }
    $isAdmin = $normalizedRole === 'admin';
    $isApplicant = $normalizedRole === 'applicant';

    $roleLabel = match ($user?->role) {
        'admin' => 'Admin',
        'hr_manager', 'hr-manager' => 'Admin',
        'applicant' => 'Applicant',
        'hr_officer' => 'HR Officer',
        default => 'Recruiter',
    };
@endphp
<div id="panelLayout" class="min-h-screen lg:flex">
    <aside id="desktopSidebar" class="hidden lg:flex lg:flex-col border-r border-border bg-white/90 backdrop-blur sticky top-0 h-screen transition-all duration-200 overflow-x-hidden">
        <div class="px-6 py-6 border-b border-border">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                <div class="h-11 w-11 rounded-2xl bg-accent text-white flex items-center justify-center font-bold">RS</div>
                <div class="sidebar-brand-copy">
                    <div class="font-bold text-lg text-white">Resume Screener</div>
                    <div class="text-sm text-white/70">Clean recruitment panel</div>
                </div>
                </div>

                <button type="button" id="sidebarToggleDesktopAlt" class="nav-btn h-10 w-10" aria-expanded="true" aria-controls="desktopSidebar" title="Close sidebar">
                    <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        <nav class="px-4 py-5 space-y-1 flex-1 overflow-y-auto">
            <p class="px-2 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold sidebar-label">{{ __('messages.main_menu') }}</p>
            <a class="sidebar-link {{ $activeNav === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                <svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5.5 10.5V20h13V10.5"/></svg>
                <span class="sidebar-label">{{ __('messages.dashboard') }}</span>
            </a>

            @if ($isAdmin)
                <a class="sidebar-link {{ $activeNav === 'admin.recruiters' ? 'active' : '' }}" href="{{ route('admin.recruiters') }}" title="{{ __('messages.recruiter_management') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 1 0-8 0m-3 7a7 7 0 0 1 14 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg><span class="sidebar-label">{{ __('messages.recruiter_management') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.jobs' ? 'active' : '' }}" href="{{ route('admin.jobs') }}" title="{{ __('messages.global_jobs') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg><span class="sidebar-label">{{ __('messages.global_jobs') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.analytics' ? 'active' : '' }}" href="{{ route('admin.analytics') }}" title="{{ __('messages.analytics') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16V9m5 7V5m5 11v-6"/></svg><span class="sidebar-label">{{ __('messages.analytics') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.reports' ? 'active' : '' }}" href="{{ route('admin.reports') }}" title="{{ __('messages.reports') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5"/></svg><span class="sidebar-label">{{ __('messages.reports') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.audit' ? 'active' : '' }}" href="{{ route('admin.audit') }}" title="{{ __('messages.audit_logs') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-9-9"/></svg><span class="sidebar-label">{{ __('messages.audit_logs') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.api' ? 'active' : '' }}" href="{{ route('admin.api') }}" title="{{ __('messages.api_usage') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5-6h3m-9 9h10a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"/></svg><span class="sidebar-label">{{ __('messages.api_usage') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'admin.system' ? 'active' : '' }}" href="{{ route('admin.system') }}" title="{{ __('messages.system_config') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1 1 0 0 1 1.35-.936l.56.255a1 1 0 0 0 .83 0l.56-.255a1 1 0 0 1 1.35.936l.065.613a1 1 0 0 0 .564.79l.524.247a1 1 0 0 1 .48 1.31l-.247.524a1 1 0 0 0 0 .83l.247.524a1 1 0 0 1-.48 1.31l-.524.247a1 1 0 0 0-.564.79l-.065.613a1 1 0 0 1-1.35.936l-.56-.255a1 1 0 0 0-.83 0l-.56.255a1 1 0 0 1-1.35-.936l-.065-.613a1 1 0 0 0-.564-.79l-.524-.247a1 1 0 0 1-.48-1.31l.247-.524a1 1 0 0 0 0-.83l-.247-.524a1 1 0 0 1 .48-1.31l.524-.247a1 1 0 0 0 .564-.79l.065-.613z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg><span class="sidebar-label">{{ __('messages.system_config') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'hr.jobs' ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}" title="{{ __('messages.job_management') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a4 4 0 0 1 8 0v2m-11 0h14a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"/></svg><span class="sidebar-label">{{ __('messages.job_management') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'hr.ranking' ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}" title="{{ __('messages.candidate_ranking') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16V9m5 7V5m5 11v-6"/></svg><span class="sidebar-label">{{ __('messages.candidate_ranking') }}</span></a>
            @elseif ($isApplicant)
                <a class="sidebar-link {{ $activeNav === 'applicant.profile' ? 'active' : '' }}" href="{{ route('applicant.profile') }}" title="{{ __('messages.applicant_profile') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 21a9 9 0 0 1 18 0"/></svg><span class="sidebar-label">{{ __('messages.applicant_profile') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'applicant.jobs' ? 'active' : '' }}" href="{{ route('applicant.jobs') }}" title="{{ __('messages.browse_jobs') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg><span class="sidebar-label">{{ __('messages.browse_jobs') }}</span></a>
            @else
                <a class="sidebar-link {{ $activeNav === 'hr.jobs' ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}" title="{{ __('messages.job_management') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a4 4 0 0 1 8 0v2m-11 0h14a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"/></svg><span class="sidebar-label">{{ __('messages.job_management') }}</span></a>
                <a class="sidebar-link {{ $activeNav === 'hr.ranking' ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}" title="{{ __('messages.candidate_ranking') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16V9m5 7V5m5 11v-6"/></svg><span class="sidebar-label">{{ __('messages.candidate_ranking') }}</span></a>
            @endif

            <a class="sidebar-link {{ $activeNav === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}" title="{{ __('messages.settings') }}"><svg class="sidebar-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1 1 0 0 1 1.35-.936l.56.255a1 1 0 0 0 .83 0l.56-.255a1 1 0 0 1 1.35.936l.065.613a1 1 0 0 0 .564.79l.524.247a1 1 0 0 1 .48 1.31l-.247.524a1 1 0 0 0 0 .83l.247.524a1 1 0 0 1-.48 1.31l-.524.247a1 1 0 0 0-.564.79l-.065.613a1 1 0 0 1-1.35.936l-.56-.255a1 1 0 0 0-.83 0l-.56.255a1 1 0 0 1-1.35-.936l-.065-.613a1 1 0 0 0-.564-.79l-.524-.247a1 1 0 0 1-.48-1.31l.247-.524a1 1 0 0 0 0-.83l-.247-.524a1 1 0 0 1 .48-1.31l.524-.247a1 1 0 0 0 .564-.79l.065-.613z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg><span class="sidebar-label">{{ __('messages.settings') }}</span></a>
        </nav>

        <div class="px-4 pb-6">
            <div class="card p-4">
                <div class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-white/60">Signed in as</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold text-lg flex-shrink-0 overflow-hidden">
                        @if ($user?->getAvatarUrl())
                            <img src="{{ $user->getAvatarUrl() }}" class="h-full w-full object-cover" alt="Profile picture" />
                        @else
                            {{ $user?->getInitials() }}
                        @endif
                    </div>
                    <div class="sidebar-user-info">
                        <div class="font-semibold text-slate-800 dark:text-white">{{ $user?->name }}</div>
                        <div class="text-sm text-slate-600 dark:text-white/70">{{ $user?->email }}</div>
                        <div class="mt-2 inline-flex rounded-full bg-slate-100 dark:bg-white/15 px-3 py-1 text-xs font-semibold text-slate-700 dark:text-white">{{ $roleLabel }}</div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        <div class="lg:hidden border-b border-border bg-white/90 backdrop-blur" id="mobileNavWrap">
            <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-xl bg-accent text-white flex items-center justify-center font-bold text-sm">RS</div>
                    <div>
                        <div class="font-semibold text-text text-sm">Resume Screener</div>
                        <div class="text-xs text-slate-500 dark:text-slate-300">{{ $roleLabel }}</div>
                    </div>
                </div>
                <button type="button" id="mobileNavToggle" class="nav-btn h-10 px-3 text-sm font-semibold" aria-expanded="false" aria-controls="mobileNavMenu">Menu</button>
            </div>

            <nav id="mobileNavMenu" class="hidden px-4 sm:px-6 pb-4">
                <div class="rounded-2xl border border-border bg-white p-3 space-y-1">
                    <a class="mobile-nav-link {{ $activeNav === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">{{ __('messages.dashboard') }}</a>
                    @if ($isAdmin)
                        <a class="mobile-nav-link {{ $activeNav === 'admin.recruiters' ? 'active' : '' }}" href="{{ route('admin.recruiters') }}">{{ __('messages.recruiter_management') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.jobs' ? 'active' : '' }}" href="{{ route('admin.jobs') }}">{{ __('messages.global_jobs') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.analytics' ? 'active' : '' }}" href="{{ route('admin.analytics') }}">{{ __('messages.analytics') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.reports' ? 'active' : '' }}" href="{{ route('admin.reports') }}">{{ __('messages.reports') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.audit' ? 'active' : '' }}" href="{{ route('admin.audit') }}">{{ __('messages.audit_logs') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.api' ? 'active' : '' }}" href="{{ route('admin.api') }}">{{ __('messages.api_usage') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'admin.system' ? 'active' : '' }}" href="{{ route('admin.system') }}">{{ __('messages.system_config') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'hr.jobs' ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}">{{ __('messages.job_management') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'hr.ranking' ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}">{{ __('messages.candidate_ranking') }}</a>
                    @elseif ($isApplicant)
                        <a class="mobile-nav-link {{ $activeNav === 'applicant.profile' ? 'active' : '' }}" href="{{ route('applicant.profile') }}">{{ __('messages.applicant_profile') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'applicant.jobs' ? 'active' : '' }}" href="{{ route('applicant.jobs') }}">{{ __('messages.browse_jobs') }}</a>
                    @else
                        <a class="mobile-nav-link {{ $activeNav === 'hr.jobs' ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}">{{ __('messages.job_management') }}</a>
                        <a class="mobile-nav-link {{ $activeNav === 'hr.ranking' ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}">{{ __('messages.candidate_ranking') }}</a>
                    @endif
                    <a class="mobile-nav-link {{ $activeNav === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">{{ __('messages.settings') }}</a>
                </div>
            </nav>
        </div>

        <header class="sticky top-0 z-30 border-b border-border bg-white/90 backdrop-blur">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
                <div>
                    <div class="text-[11px] sm:text-xs uppercase tracking-[0.18em] text-muted">{{ __('messages.app_name') }}</div>
                    <h1 id="desktopSidebarTitle" class="page-title text-xl sm:text-3xl font-bold text-text">{{ $pageHeading ?? __('messages.panel') }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" id="sidebarToggleDesktop" class="nav-btn h-11 px-3 hidden lg:inline-flex" aria-expanded="true" aria-controls="desktopSidebar" title="Toggle sidebar">
                        <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <button type="button" id="themeToggle" class="nav-btn h-11 px-3" aria-label="Toggle theme">
                        <span id="themeToggleText" class="text-sm font-semibold">Dark</span>
                    </button>
                    @include('partials.language-switcher')

                    <button type="button" class="nav-btn h-11 w-11 relative hidden sm:inline-flex" aria-label="Notifications">
                        <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.75h5.893l-1.594-1.594A2 2 0 0 1 19 14.75V11a7 7 0 1 0-14 0v3.75a2 2 0 0 1-.156.782L3.25 17.75h5.893m5.714 0a3 3 0 1 1-5.714 0m5.714 0H9.143"/>
                        </svg>
                        <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500"></span>
                    </button>

                    <div class="relative" id="profileMenuWrap">
                        <button type="button" id="profileMenuBtn" class="nav-btn px-3 h-11">
                            <span class="hidden sm:inline text-sm font-semibold">{{ $user?->name }}</span>
                            <span class="h-8 w-8 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold text-sm flex-shrink-0 overflow-hidden">
                                @if ($user?->getAvatarUrl())
                                    <img src="{{ $user->getAvatarUrl() }}" class="h-full w-full object-cover" alt="Profile picture" />
                                @else
                                    {{ $user?->getInitials() }}
                                @endif
                            </span>
                        </button>

                        <div id="profileMenu" class="hidden absolute right-0 mt-3 w-72 rounded-2xl border border-border bg-white shadow-soft p-3">
                            <div class="px-3 py-2 border-b border-border flex items-center gap-3">
                                <div class="h-12 w-12 rounded-full bg-accentSoft text-accent flex items-center justify-center font-bold flex-shrink-0 overflow-hidden">
                                    @if ($user?->getAvatarUrl())
                                        <img src="{{ $user->getAvatarUrl() }}" class="h-full w-full object-cover" alt="Profile picture" />
                                    @else
                                        {{ $user?->getInitials() }}
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-text">{{ $user?->name }}</div>
                                    <div class="text-sm text-muted">{{ $user?->email }}</div>
                                    <div class="mt-2 inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent">{{ $roleLabel }}</div>
                                </div>
                            </div>
                            <div class="p-2 space-y-1">
                                <a href="{{ route('settings') }}" class="flex items-center rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('messages.profile') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50">{{ __('messages.logout') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            @if (session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ __('messages.fix_highlighted_fields') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-border bg-white/70 dark:bg-slate-900/70">
            <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between text-muted">
                <p>&copy; {{ now()->year }} {{ __('messages.app_name') }}. {{ __('messages.all_rights_reserved') }}</p>
                <p>{{ __('messages.unified_recruitment_workspace') }}</p>
            </div>
        </footer>
    </div>
</div>

<script>
    const profileMenuBtn = document.getElementById('profileMenuBtn');
    const profileMenu = document.getElementById('profileMenu');
    const profileMenuWrap = document.getElementById('profileMenuWrap');
    const mobileNavWrap = document.getElementById('mobileNavWrap');
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    const themeToggle = document.getElementById('themeToggle');
    const themeToggleText = document.getElementById('themeToggleText');
    const panelLayout = document.getElementById('panelLayout');
    const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
    const sidebarToggleDesktopAlt = document.getElementById('sidebarToggleDesktopAlt');
    const SIDEBAR_STORAGE_KEY = 'panel-sidebar-collapsed';

    const syncThemeLabel = () => {
        if (!themeToggleText) return;
        const isDark = document.documentElement.classList.contains('dark');
        themeToggleText.textContent = isDark ? 'Light' : 'Dark';
    };

    themeToggle?.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        syncThemeLabel();
    });

    syncThemeLabel();

    const syncSidebarState = () => {
        if (!panelLayout) {
            return;
        }

        const isCollapsed = panelLayout.classList.contains('sidebar-collapsed');

        if (sidebarToggleDesktop) {
            sidebarToggleDesktop.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            sidebarToggleDesktop.setAttribute('title', isCollapsed ? 'Open sidebar' : 'Close sidebar');
        }

        if (sidebarToggleDesktopAlt) {
            sidebarToggleDesktopAlt.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            sidebarToggleDesktopAlt.setAttribute('title', isCollapsed ? 'Open sidebar' : 'Close sidebar');
        }
    };

    const toggleSidebar = () => {
        if (!panelLayout) {
            return;
        }

        panelLayout.classList.toggle('sidebar-collapsed');
        const isCollapsed = panelLayout.classList.contains('sidebar-collapsed');
        localStorage.setItem(SIDEBAR_STORAGE_KEY, isCollapsed ? '1' : '0');
        syncSidebarState();
    };

    if (panelLayout) {
        const savedCollapsed = localStorage.getItem(SIDEBAR_STORAGE_KEY) === '1';
        panelLayout.classList.toggle('sidebar-collapsed', savedCollapsed);
        syncSidebarState();

        sidebarToggleDesktop?.addEventListener('click', toggleSidebar);
        sidebarToggleDesktopAlt?.addEventListener('click', toggleSidebar);
    }

    profileMenuBtn?.addEventListener('click', () => {
        profileMenu?.classList.toggle('hidden');
    });

    mobileNavToggle?.addEventListener('click', () => {
        mobileNavMenu?.classList.toggle('hidden');
        const isExpanded = !mobileNavMenu?.classList.contains('hidden');
        mobileNavToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
        if (profileMenuWrap && profileMenu && !profileMenuWrap.contains(event.target)) {
            profileMenu.classList.add('hidden');
        }

        if (mobileNavWrap && mobileNavMenu && mobileNavToggle && !mobileNavWrap.contains(event.target)) {
            mobileNavMenu.classList.add('hidden');
            mobileNavToggle.setAttribute('aria-expanded', 'false');
        }
    });
</script>

@yield('scripts')
</body>
</html>
