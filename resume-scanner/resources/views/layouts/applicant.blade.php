<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle ?? __('messages.applicant_portal') }} — Smart Recruitment</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Immediate dark-mode flash prevention --}}
    <script>
        (function () {
            const t = localStorage.getItem('ap-theme');
            const prefer = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (!t && prefer)) document.documentElement.classList.add('dark');
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary:    '#2563EB',
                        primaryHov: '#1D4ED8',
                        primarySoft:'#DBEAFE',
                        surface:    '#FFFFFF',
                        surfaceD:   '#1E293B',
                        base:       '#F1F5F9',
                        baseD:      '#0F172A',
                        border:     '#E2E8F0',
                        borderD:    '#334155',
                        text:       '#0F172A',
                        muted:      '#64748B',
                        accent:     '#2563EB',
                        accentSoft: '#DBEAFE',
                    }
                }
            }
        }
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; font-family: 'Inter', sans-serif; }

        body {
            background: #F1F5F9;
            color: #0F172A;
            transition: background .2s, color .2s;
        }
        html.dark body { background: #0F172A; color: #E2E8F0; }

        /* ── Sidebar ── */
        #ap-sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: 260px; z-index: 50;
            background: linear-gradient(180deg, #4C1D95 0%, #7C3AED 48%, #0F172A 100%);
            border-right: 1px solid #E2E8F0;
            display: flex; flex-direction: column;
            transform: translateX(-100%);
            transition: transform .25s cubic-bezier(.4,0,.2,1);
            overflow-y: auto;
            color: #E2E8F0;
        }
        html.dark #ap-sidebar { background: linear-gradient(180deg, #111827 0%, #5B21B6 55%, #1E293B 100%); border-color: #334155; }
        #ap-sidebar.open { transform: translateX(0); }

        body.ap-sidebar-collapsed #ap-sidebar { transform: translateX(-100%); }
        body.ap-sidebar-collapsed #ap-main { margin-left: 0; }

        /* Desktop: sidebar always visible */
        @media (min-width: 1024px) {
            #ap-sidebar { transform: translateX(0); }
            #ap-main { margin-left: 260px; }

            body.ap-sidebar-collapsed #ap-sidebar { transform: translateX(-100%); }
            body.ap-sidebar-collapsed #ap-main { margin-left: 0; }
        }

        #ap-overlay {
            display: none;
            position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 49;
        }
        #ap-overlay.show { display: block; }

        /* ── Nav links ── */
        .ap-nav-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1rem; border-radius: .85rem;
            color: rgba(226,232,240,.9); text-decoration: none;
            font-size: .875rem; font-weight: 500;
            transition: background .15s, color .15s;
        }
        .ap-nav-link:hover { background: rgba(255,255,255,.12); color: #FFFFFF; }
        .ap-nav-link.active { background: rgba(255,255,255,.18); color: #FFFFFF; font-weight: 600; }
        html.dark .ap-nav-link { color: rgba(226,232,240,.86); }
        html.dark .ap-nav-link:hover { background: rgba(255,255,255,.12); color: #FFFFFF; }
        html.dark .ap-nav-link.active { background: rgba(255,255,255,.18); color: #FFFFFF; }

        .ap-nav-icon { width: 1.15rem; height: 1.15rem; flex-shrink: 0; }

        /* ── Stat cards ── */
        .stat-card {
            background: #fff;
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 20px rgba(15,23,42,.06);
            transition: transform .15s, box-shadow .15s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(15,23,42,.1); }
        html.dark .stat-card { background: #1E293B; box-shadow: 0 4px 20px rgba(0,0,0,.3); }

        /* ── Card ── */
        .ap-card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 20px rgba(15,23,42,.06);
        }
        html.dark .ap-card { background: #1E293B; box-shadow: 0 4px 20px rgba(0,0,0,.3); }

        .card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 20px rgba(15,23,42,.06);
        }
        html.dark .card { background: #1E293B; box-shadow: 0 4px 20px rgba(0,0,0,.3); }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: .75rem;
            border: 1.5px solid #E2E8F0;
            background: #FFFFFF;
            color: #475569;
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
        }

        .nav-btn:hover { background: #F1F5F9; color: #2563EB; }
        html.dark .nav-btn { border-color: #334155; background: #1E293B; color: #94A3B8; }
        html.dark .nav-btn:hover { background: #0F172A; color: #93C5FD; }

        .nav-btn-primary {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #fff;
            border-color: #2563EB;
        }

        .nav-btn-primary:hover { opacity: .92; }

        .text-text { color: #0F172A; }
        html.dark .text-text { color: #E2E8F0; }

        .text-muted { color: #64748B; }
        html.dark .text-muted { color: #94A3B8; }

        .border-border { border-color: #E2E8F0; }
        html.dark .border-border { border-color: #334155 !important; }

        .bg-accentSoft { background: #DBEAFE; }
        html.dark .bg-accentSoft { background: #1E3A8A50; }

        .text-accent { color: #2563EB; }
        html.dark .text-accent { color: #93C5FD; }

        /* ── Progress bar ── */
        .progress-bar-track { background: #E2E8F0; border-radius: 999px; height: .5rem; overflow: hidden; }
        html.dark .progress-bar-track { background: #334155; }
        .progress-bar-fill { background: linear-gradient(90deg, #2563EB, #3B82F6); border-radius: 999px; height: 100%; transition: width .6s ease; }

        /* ── Step tracker ── */
        .step-done .step-dot { background: #2563EB; border-color: #2563EB; }
        .step-done .step-label { color: #2563EB; font-weight: 600; }
        .step-pending .step-dot { background: #fff; border-color: #CBD5E1; }
        html.dark .step-pending .step-dot { background: #1E293B; border-color: #475569; }

        /* ── Button ── */
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #fff; padding: .55rem 1.1rem; border-radius: .75rem;
            font-size: .8rem; font-weight: 600; text-decoration: none; border: none;
            cursor: pointer; transition: opacity .15s, transform .15s;
        }
        .btn-primary:hover { opacity: .92; transform: translateY(-1px); }

        .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            background: transparent; border: 1.5px solid #E2E8F0;
            color: #475569; padding: .5rem 1rem; border-radius: .75rem;
            font-size: .8rem; font-weight: 500; text-decoration: none;
            cursor: pointer; transition: background .15s, color .15s;
        }
        .btn-ghost:hover { background: #F1F5F9; color: #2563EB; }
        html.dark .btn-ghost { border-color: #334155; color: #94A3B8; }
        html.dark .btn-ghost:hover { background: #1E293B; color: #93C5FD; }

        /* ── Notification badge ── */
        .notif-badge {
            position: absolute; top: 2px; right: 2px;
            min-width: 1rem; height: 1rem; padding: 0 .25rem;
            background: #EF4444; color: #fff; font-size: .6rem;
            font-weight: 700; border-radius: 999px;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none;
        }

        /* ── Tag / Chip ── */
        .chip { display: inline-flex; align-items: center; padding: .25rem .7rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
        .chip-blue { background: #DBEAFE; color: #1D4ED8; }
        .chip-green { background: #D1FAE5; color: #065F46; }
        .chip-amber { background: #FEF3C7; color: #92400E; }
        .chip-red { background: #FEE2E2; color: #991B1B; }
        .chip-slate { background: #F1F5F9; color: #475569; }
        html.dark .chip-blue { background: #1E3A8A40; color: #93C5FD; }
        html.dark .chip-green { background: #06402540; color: #6EE7B7; }
        html.dark .chip-amber { background: #92400E40; color: #FCD34D; }
        html.dark .chip-red { background: #99112340; color: #FCA5A5; }
        html.dark .chip-slate { background: #33415540; color: #94A3B8; }

        /* ── Table ── */
        .ap-table th { background: #F8FAFC; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #64748B; font-weight: 600; }
        html.dark .ap-table th { background: #0F172A; color: #64748B; }
        html.dark .ap-table td { border-color: #334155; }

        /* ── Chat bot ── */
        #chat-fab {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 60;
            width: 3.25rem; height: 3.25rem; border-radius: 999px;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #fff; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 24px rgba(37,99,235,.4);
            transition: transform .15s, box-shadow .15s;
        }
        #chat-fab:hover { transform: scale(1.08); box-shadow: 0 8px 30px rgba(37,99,235,.55); }

        #chat-window {
            position: fixed; bottom: 5.5rem; right: 1.5rem; z-index: 60;
            width: 300px; background: #fff; border-radius: 1.25rem;
            box-shadow: 0 12px 40px rgba(15,23,42,.15);
            display: none; flex-direction: column; overflow: hidden;
        }
        html.dark #chat-window { background: #1E293B; }
        #chat-window.show { display: flex; }

        #chat-messages { flex: 1; padding: .75rem; overflow-y: auto; max-height: 220px; }
        #chat-messages .bot-msg { background: #EFF6FF; border-radius: .75rem .75rem .75rem 0; padding: .6rem .75rem; font-size: .8rem; margin-bottom: .5rem; max-width: 85%; }
        html.dark #chat-messages .bot-msg { background: #1E3A8A30; color: #93C5FD; }
        #chat-input { border: none; border-top: 1px solid #E2E8F0; padding: .6rem .75rem; font-size: .8rem; outline: none; background: transparent; color: inherit; }
        html.dark #chat-input { border-color: #334155; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 999px; }
        html.dark ::-webkit-scrollbar-thumb { background: #334155; }

        /* ── Toast ── */
        #ap-toast {
            position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
            background: #1E293B; color: #F8FAFC; padding: .65rem 1.25rem;
            border-radius: .85rem; font-size: .8rem; z-index: 70;
            opacity: 0; pointer-events: none; transition: opacity .2s;
        }
        #ap-toast.show { opacity: 1; }
    </style>
</head>
<body class="h-full">
@php
    $user     = auth()->user();
    $apNav    = $activeNav ?? 'dashboard';
    $apName   = $user?->first_name ?? explode(' ', $user?->name ?? 'Applicant')[0];
    $apAvatar = $user?->getAvatarUrl();
    $apInit   = $user?->getInitials() ?? 'A';

    $unreadCount = \App\Models\Notification::query()
        ->where('user_id', (int) ($user?->id ?? 0))
        ->whereNull('read_at')
        ->count();
@endphp

{{-- ── Sidebar ── --}}
<aside id="ap-sidebar" role="navigation" aria-label="Applicant navigation">
    <div class="px-5 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-white font-bold text-sm select-none">SR</div>
        <div>
                <div class="font-bold text-sm text-white leading-tight">Smart Recruitment</div>
                <div class="text-xs text-white/70">{{ __('messages.applicant_portal') }}</div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-0.5">
        <p class="px-3 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold">{{ __('messages.main_menu') }}</p>

        <a href="{{ route('applicant.dashboard') }}" class="ap-nav-link {{ $apNav === 'dashboard' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v9h5v-5h4v5h5v-9"/></svg>
            {{ __('messages.my_dashboard') }}
        </a>
        <a href="{{ route('applicant.jobs') }}" class="ap-nav-link {{ $apNav === 'applicant.jobs' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            {{ __('messages.job_vacancies') }}
        </a>
        <a href="{{ route('applicant.applications') }}" class="ap-nav-link {{ $apNav === 'applicant.applications' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
            {{ __('messages.my_applications') }}
        </a>
        <a href="{{ route('applicant.recommendations') }}" class="ap-nav-link {{ $apNav === 'applicant.recommendations' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.07 4.93A10 10 0 1 1 4.93 19.07"/></svg>
            {{ __('messages.ai_recommendations') }}
        </a>
        <a href="{{ route('applicant.interviews') }}" class="ap-nav-link {{ $apNav === 'applicant.interviews' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
            {{ __('messages.interviews') }}
        </a>
        <a href="{{ route('applicant.notifications') }}" class="ap-nav-link {{ $apNav === 'applicant.notifications' ? 'active' : '' }}">
            <span class="relative">
                <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unreadCount > 0)<span class="absolute -top-1 -right-1 bg-red-500 text-white text-[.55rem] font-bold rounded-full min-w-[.9rem] h-[.9rem] flex items-center justify-center px-0.5">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
            </span>
            {{ __('messages.notifications') }}
        </a>

        <p class="px-3 mt-4 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold">{{ __('messages.documents') }}</p>
        <a href="{{ route('applicant.downloads') }}" class="ap-nav-link {{ $apNav === 'applicant.downloads' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-8m0 8l-3-3m3 3l3-3"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-2"/></svg>
            {{ __('messages.download_center') }}
        </a>

        <p class="px-3 mt-4 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold">{{ __('messages.account') }}</p>
        <a href="{{ route('applicant.profile') }}" class="ap-nav-link {{ $apNav === 'applicant.profile' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            {{ __('messages.my_profile') }}
        </a>
        <a href="{{ route('settings') }}" class="ap-nav-link {{ $apNav === 'settings' ? 'active' : '' }}">
            <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1 1 0 011.35-.936l.56.255a1 1 0 00.83 0l.56-.255a1 1 0 011.35.936l.065.613a1 1 0 00.564.79l.524.247a1 1 0 01.48 1.31l-.247.524a1 1 0 000 .83l.247.524a1 1 0 01-.48 1.31l-.524.247a1 1 0 00-.564.79l-.065.613a1 1 0 01-1.35.936l-.56-.255a1 1 0 00-.83 0l-.56.255a1 1 0 01-1.35-.936l-.065-.613a1 1 0 00-.564-.79l-.524-.247a1 1 0 01-.48-1.31l.247-.524a1 1 0 000-.83l-.247-.524a1 1 0 01.48-1.31l.524-.247a1 1 0 00.564-.79l.065-.613z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('messages.settings') }}
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ap-nav-link w-full text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                <svg class="ap-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>
                {{ __('messages.logout') }}
            </button>
        </form>
    </nav>

    <div class="p-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
            <div class="relative flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm overflow-hidden">
                    @if($apAvatar)<img src="{{ $apAvatar }}" class="h-full w-full object-cover" alt="{{ $apName }}" />@else{{ $apInit }}@endif
                </div>
                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 border-2 border-white dark:border-slate-800"></span>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-white truncate">{{ $user?->name }}</div>
                <div class="text-xs text-white/70 truncate">{{ $user?->email }}</div>
            </div>
        </div>
    </div>
</aside>

{{-- Overlay --}}
<div id="ap-overlay" aria-hidden="true"></div>

{{-- ── Main ── --}}
<div id="ap-main" class="min-h-screen flex flex-col transition-[margin] duration-200">

    {{-- Top navbar --}}
    <header class="sticky top-0 z-40 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 sm:px-6 h-16 flex items-center gap-3">
        <button id="ap-menu-btn" type="button" aria-label="Open menu" class="flex-shrink-0 h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <button id="ap-sidebar-toggle-desktop" type="button" aria-label="Toggle sidebar" aria-expanded="true" class="hidden lg:flex flex-shrink-0 h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 items-center justify-center text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>

        {{-- Search --}}
        <form action="{{ route('applicant.jobs') }}" method="GET" class="flex-1 max-w-sm hidden sm:flex items-center gap-2 bg-slate-100 dark:bg-slate-800 rounded-xl px-3 py-2">
            <svg class="h-4 w-4 text-muted flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
            <input name="q" type="text" placeholder="{{ __('messages.search_vacancies') }}" class="flex-1 bg-transparent text-sm outline-none text-slate-700 dark:text-slate-200 placeholder-muted" />
        </form>

        <div class="flex-1"></div>

        @if(session('impersonator_id'))
            <form method="POST" action="{{ route('admin.impersonation.leave') }}">
                @csrf
                <button type="submit" class="h-10 rounded-xl border border-amber-300 bg-amber-50 px-3 text-xs font-semibold text-amber-700 hover:bg-amber-100">{{ __('messages.return_to_admin') }}</button>
            </form>
        @endif

        @include('partials.language-switcher')

        {{-- Dark mode toggle --}}
        <button id="ap-theme-btn" type="button" aria-label="Toggle dark mode" class="h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
            <svg id="ap-sun" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <svg id="ap-moon" class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </button>

        {{-- Notification bell --}}
        <div class="relative">
            <button id="ap-notif-btn" type="button" aria-label="Notifications" class="relative h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unreadCount > 0)
                    <span class="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>

            <div id="ap-notif-dropdown" class="hidden absolute right-0 mt-2 w-72 ap-card py-2 z-50 border border-slate-100 dark:border-slate-700">
                <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-muted border-b border-slate-100 dark:border-slate-700">{{ __('messages.notifications') }}</div>
                @forelse(\App\Models\Notification::query()->where('user_id', $user?->id)->latest()->limit(5)->get() as $n)
                    <div class="px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm border-b border-slate-50 dark:border-slate-800 last:border-0">
                        <div class="font-medium text-slate-700 dark:text-slate-200">{{ $n->title }}</div>
                        <div class="text-xs text-muted mt-0.5">{{ $n->message }}</div>
                    </div>
                @empty
                    <div class="px-4 py-3 text-sm text-muted">{{ __('messages.no_notifications') }}</div>
                @endforelse
                <div class="px-4 pt-2 pb-1"><a href="{{ route('applicant.notifications') }}" class="text-xs font-semibold text-primary hover:underline">{{ __('messages.view_all') }}</a></div>
            </div>
        </div>

        {{-- Profile avatar --}}
        <div class="relative">
            <button id="ap-avatar-btn" type="button" class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm overflow-hidden border-2 border-blue-100 dark:border-blue-900 flex-shrink-0">
                @if($apAvatar)<img src="{{ $apAvatar }}" class="h-full w-full object-cover" alt="{{ $apName }}" />@else{{ $apInit }}@endif
            </button>
            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 border-2 border-white dark:border-slate-900 pointer-events-none"></span>

            <div id="ap-profile-dropdown" class="hidden absolute right-0 mt-2 w-48 ap-card py-2 z-50 border border-slate-100 dark:border-slate-700">
                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $user?->name }}</div>
                    <div class="text-xs text-muted">{{ $user?->email }}</div>
                </div>
                <a href="{{ route('applicant.profile') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">{{ __('messages.my_profile') }}</a>
                <a href="{{ route('settings') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">{{ __('messages.settings') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">{{ __('messages.logout') }}</button>
                </form>
            </div>
        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('success') || session('error'))
        <div class="px-4 sm:px-6 pt-4">
            @if(session('success'))
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</div>
            @endif
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 sm:px-6 pt-4">
            <div class="rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="flex-1 px-4 sm:px-6 py-6">
        @yield('content')
    </main>

    <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70">
        <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between text-muted">
            <p>&copy; {{ now()->year }} Smart Recruitment. {{ __('messages.all_rights_reserved_sentence') }}</p>
            <p>{{ __('messages.applicant_self_service_portal') }}</p>
        </div>
    </footer>
</div>

{{-- ── Chat Bot FAB ── --}}
<button id="chat-fab" aria-label="Open chat support">
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.862 9.862 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
</button>

<div id="chat-window" role="dialog" aria-label="Chat Support">
    <div class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.07 4.93A10 10 0 114.93 19.07"/></svg>
        <span class="text-sm font-semibold">AI Support</span>
        <button id="chat-close" type="button" class="ml-auto opacity-80 hover:opacity-100" aria-label="Close chat">✕</button>
    </div>
    <div id="chat-messages">
        <div class="bot-msg">👋 Hi {{ $apName }}! How can I help you today?</div>
    </div>

    <form id="chat-cv-upload-form" method="POST" action="{{ route('applicant.chatbot.upload-cv') }}" enctype="multipart/form-data" class="px-3 py-2 border-t border-slate-100 dark:border-slate-700">
        @csrf
        <label for="chat-cv-input" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Quick CV Upload</label>
        <div class="mt-1.5 flex items-center gap-2">
            <input id="chat-cv-input" type="file" name="cv" accept=".pdf,.doc,.docx" class="block w-full text-xs text-slate-600 dark:text-slate-300 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-blue-700 dark:file:bg-blue-900/40 dark:file:text-blue-200" required />
            <button type="submit" class="btn-primary text-xs px-2.5 py-1.5 whitespace-nowrap">Upload</button>
        </div>
        <p class="mt-1 text-[11px] text-muted">PDF, DOC, DOCX up to 5MB.</p>
    </form>

    <input id="chat-input" type="text" placeholder="Ask something..." autocomplete="off" />
</div>

{{-- ── Toast ── --}}
<div id="ap-toast" role="status" aria-live="polite"></div>

<script>
(function () {
    /* ── Sidebar toggle ── */
    const sidebar  = document.getElementById('ap-sidebar');
    const overlay  = document.getElementById('ap-overlay');
    const menuBtn  = document.getElementById('ap-menu-btn');
    const desktopToggle = document.getElementById('ap-sidebar-toggle-desktop');
    const storageKey = 'ap-sidebar-collapsed';
    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    const openSidebar  = () => { sidebar.classList.add('open'); overlay.classList.add('show'); };
    const closeSidebar = () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); };

    const setCollapsed = (collapsed) => {
        document.body.classList.toggle('ap-sidebar-collapsed', collapsed);
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
        desktopToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    menuBtn?.addEventListener('click', () => {
        if (isDesktop()) {
            setCollapsed(!document.body.classList.contains('ap-sidebar-collapsed'));
            return;
        }

        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    desktopToggle?.addEventListener('click', () => setCollapsed(!document.body.classList.contains('ap-sidebar-collapsed')));
    overlay?.addEventListener('click', closeSidebar);
    setCollapsed(localStorage.getItem(storageKey) === '1');

    /* ── Dark mode ── */
    const themeBtn = document.getElementById('ap-theme-btn');
    themeBtn?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('ap-theme', isDark ? 'dark' : 'light');
    });

    /* ── Notification dropdown ── */
    const notifBtn = document.getElementById('ap-notif-btn');
    const notifDrop = document.getElementById('ap-notif-dropdown');
    notifBtn?.addEventListener('click', (e) => { e.stopPropagation(); notifDrop?.classList.toggle('hidden'); profileDrop?.classList.add('hidden'); });

    /* ── Profile dropdown ── */
    const avatarBtn  = document.getElementById('ap-avatar-btn');
    const profileDrop = document.getElementById('ap-profile-dropdown');
    avatarBtn?.addEventListener('click', (e) => { e.stopPropagation(); profileDrop?.classList.toggle('hidden'); notifDrop?.classList.add('hidden'); });

    document.addEventListener('click', () => { notifDrop?.classList.add('hidden'); profileDrop?.classList.add('hidden'); });

    /* ── Chat bot ── */
    const chatFab    = document.getElementById('chat-fab');
    const chatWindow = document.getElementById('chat-window');
    const chatClose  = document.getElementById('chat-close');
    const chatInput  = document.getElementById('chat-input');
    const chatMsgs   = document.getElementById('chat-messages');
    const chatCvUploadForm = document.getElementById('chat-cv-upload-form');
    const chatCvInput = document.getElementById('chat-cv-input');

    const addMsg = (text, type) => {
        const div = document.createElement('div');
        div.className = type === 'user'
            ? 'text-right mb-2 text-xs text-slate-600 dark:text-slate-400'
            : 'bot-msg';
        div.textContent = text;
        chatMsgs?.appendChild(div);
        if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const chatApiUrl = '{{ route('applicant.chatbot.message') }}';

    const sendChatQuestion = async (question) => {
        addMsg(question, 'user');
        chatInput.value = '';

        const loading = document.createElement('div');
        loading.className = 'bot-msg bot-loading';
        loading.textContent = '⏳ Inatumia AI support...';
        chatMsgs?.appendChild(loading);
        if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;

        try {
            const response = await fetch(chatApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ question }),
            });

            const data = await response.json();
            loading.remove();

            if (!response.ok || typeof data.reply !== 'string') {
                addMsg('AI support haikuwe able kuleta jibu sahihi. Jaribu tena tafadhali.', 'bot');
                return;
            }

            addMsg(data.reply, 'bot');
        } catch (error) {
            loading.remove();
            addMsg('Kuna tatizo kuwasiliana na AI support. Jaribu tena baadaye.', 'bot');
            console.error(error);
        }
    };

    chatFab?.addEventListener('click', () => chatWindow?.classList.toggle('show'));
    chatClose?.addEventListener('click', () => chatWindow?.classList.remove('show'));
    chatCvUploadForm?.addEventListener('submit', () => {
        if (chatCvInput?.files && chatCvInput.files.length > 0) {
            addMsg('Uploading CV...', 'bot');
        }
    });
    chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && chatInput.value.trim()) {
            sendChatQuestion(chatInput.value.trim());
        }
    });

    /* ── Toast helper ── */
    window.apToast = (msg, duration = 3000) => {
        const el = document.getElementById('ap-toast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), duration);
    };

    /* ── Scroll-to anchor smooth ── */
    document.querySelectorAll('a[href*="#"]').forEach(a => {
        const hash = a.getAttribute('href')?.split('#')[1];
        if (!hash) return;
        a.addEventListener('click', (e) => {
            const target = document.getElementById(hash);
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
})();
</script>

@yield('scripts')
</body>
</html>
