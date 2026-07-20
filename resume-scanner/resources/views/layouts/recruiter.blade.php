<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? __('messages.recruiter_panel') }} - Smart Recruitment</title>

    <script>
        (function () {
            const t = localStorage.getItem('recruiter-theme');
            const prefer = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (!t && prefer)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#1D4ED8',
                        primarySoft: '#DBEAFE',
                        border: '#E2E8F0',
                        text: '#0F172A',
                        muted: '#64748B'
                    },
                    boxShadow: {
                        panel: '0 16px 38px rgba(15, 23, 42, 0.08)'
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; color: #0F172A; }
        html.dark body { background: #020617; color: #E2E8F0; }

        .rc-sidebar-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .72rem .95rem;
            border-radius: .85rem;
            color: rgba(226, 232, 240, 0.9);
            font-size: .86rem;
            font-weight: 500;
            transition: all .15s ease;
            text-decoration: none;
        }

        .rc-sidebar-link:hover { background: rgba(255, 255, 255, 0.12); color: #FFFFFF; }
        .rc-sidebar-link.active { background: rgba(255, 255, 255, 0.18); color: #FFFFFF; font-weight: 600; }

        html.dark .rc-sidebar-link { color: rgba(226, 232, 240, 0.86); }
        html.dark .rc-sidebar-link:hover { background: rgba(255, 255, 255, 0.12); color: #FFFFFF; }
        html.dark .rc-sidebar-link.active { background: rgba(255, 255, 255, 0.18); color: #FFFFFF; }

        .rc-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        }

        .card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: .75rem;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            color: #0F172A;
            text-decoration: none;
            transition: all .15s ease;
        }

        .nav-btn:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            border-color: #1D4ED8;
            color: #FFFFFF;
        }

        .nav-btn-primary:hover {
            filter: brightness(.95);
        }

        .text-text {
            color: #0F172A;
        }

        .text-muted {
            color: #64748B;
        }

        .border-border {
            border-color: #E2E8F0;
        }

        .bg-accentSoft {
            background: #DBEAFE;
        }

        .text-accent {
            color: #1D4ED8;
        }

        .bg-accent {
            background: #2563EB;
        }

        html.dark .rc-card {
            background: #0F172A;
            border-color: #1E293B;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.45);
        }

        html.dark .card {
            background: #0F172A;
            border-color: #1E293B;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.45);
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

        html.dark .nav-btn-primary {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            border-color: #1D4ED8;
            color: #FFFFFF;
        }

        html.dark .text-text {
            color: #E2E8F0;
        }

        html.dark .text-muted {
            color: #94A3B8;
        }

        html.dark .border-border {
            border-color: #1E293B !important;
        }

        html.dark .bg-accentSoft {
            background: #1E3A8A40;
        }

        html.dark .text-accent {
            color: #93C5FD;
        }

        html.dark .bg-accent {
            background: #2563EB;
        }

        #rc-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            z-index: 50;
            background: linear-gradient(180deg, #052E2B 0%, #0F766E 54%, #0F172A 100%);
            border-right: 1px solid #E2E8F0;
            transform: translateX(-100%);
            transition: transform .2s ease;
            display: flex;
            flex-direction: column;
            color: #E2E8F0;
        }

        html.dark #rc-sidebar { background: linear-gradient(180deg, #020617 0%, #134E4A 52%, #0F172A 100%); border-color: #1E293B; }
        #rc-sidebar.open { transform: translateX(0); }

        body.rc-sidebar-collapsed #rc-sidebar { transform: translateX(-100%); }
        body.rc-sidebar-collapsed #rc-main { margin-left: 0; }

        #rc-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(15, 23, 42, 0.48);
        }

        #rc-overlay.show { display: block; }

        @media (min-width: 1024px) {
            #rc-sidebar { transform: translateX(0); }
            #rc-main { margin-left: 280px; }

            body.rc-sidebar-collapsed #rc-sidebar { transform: translateX(-100%); }
            body.rc-sidebar-collapsed #rc-main { margin-left: 0; }
        }

        .rc-badge {
            min-width: 1rem;
            height: 1rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 700;
            background: #EF4444;
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 .25rem;
        }

        .rc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            padding: .45rem .9rem;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid #BFDBFE;
            color: #1D4ED8;
            background: #EFF6FF;
            text-decoration: none;
            transition: all .15s ease;
        }

        .rc-btn:hover { background: #DBEAFE; }

        .rc-btn-primary {
            color: #FFFFFF;
            border-color: #1D4ED8;
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
        }

        .rc-btn-primary:hover { filter: brightness(0.95); }

        html.dark .rc-btn { border-color: #1E3A8A; background: #1E3A8A40; color: #93C5FD; }

        /* ── Chat bot ── */
        #chat-fab {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 60;
            width: 3.25rem; height: 3.25rem; border-radius: 999px;
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: #fff; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 24px rgba(29,78,216,.4);
            transition: transform .15s, box-shadow .15s;
        }
        #chat-fab:hover { transform: scale(1.08); box-shadow: 0 8px 30px rgba(29,78,216,.55); }

        #chat-window {
            position: fixed; bottom: 5.5rem; right: 1.5rem; z-index: 60;
            width: 320px; background: #fff; border-radius: 1.25rem;
            box-shadow: 0 12px 40px rgba(15,23,42,.15);
            display: none; flex-direction: column; overflow: hidden;
        }
        html.dark #chat-window { background: #1E293B; }
        #chat-window.show { display: flex; }

        #chat-messages { flex: 1; padding: .75rem; overflow-y: auto; max-height: 260px; }
        #chat-messages .bot-msg { background: #EFF6FF; border-radius: .75rem .75rem .75rem 0; padding: .6rem .75rem; font-size: .8rem; margin-bottom: .5rem; max-width: 85%; }
        html.dark #chat-messages .bot-msg { background: #1E3A8A30; color: #93C5FD; }
        #chat-input { border: none; border-top: 1px solid #E2E8F0; padding: .6rem .75rem; font-size: .8rem; outline: none; background: transparent; color: inherit; width: 100%; }
        html.dark #chat-input { border-color: #334155; }
    </style>
</head>
<body class="h-full">
@php
    $active = $active ?? $activeNav ?? 'dashboard';
    $user = auth()->user();
    $avatar = $user?->getAvatarUrl();
    $initials = $user?->getInitials() ?? 'HR';
    $notifCount = (int) ($dashboard['unreadNotifications'] ?? \App\Models\Notification::query()
        ->where('user_id', (int) ($user?->id ?? 0))
        ->whereNull('read_at')
        ->count());
@endphp

<aside id="rc-sidebar" aria-label="Recruiter navigation">
    <div class="p-5 border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 text-white font-bold text-sm flex items-center justify-center">RS</div>
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-white/70">{{ __('messages.recruiter_panel') }}</p>
                <h1 class="font-bold text-white leading-tight">Smart Recruitment</h1>
            </div>
        </div>
    </div>

    <nav class="px-3 py-4 space-y-1 text-sm flex-1 overflow-y-auto">
        <p class="px-3 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold">{{ __('messages.main_menu') }}</p>
        <a class="rc-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('hr.dashboard') }}">{{ __('messages.dashboard') }}</a>
        <a class="rc-sidebar-link {{ in_array($active, ['hr.jobs', 'jobs', 'jobs.index'], true) ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}">{{ __('messages.vacancy_management') }}</a>
        <a class="rc-sidebar-link {{ in_array($active, ['hr.ranking', 'hr.candidate.ranking'], true) ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}">{{ __('messages.applicants') }}</a>
        <a class="rc-sidebar-link {{ $active === 'interviews' ? 'active' : '' }}" href="{{ route('hr.interviews') }}">{{ __('messages.interviews') }}</a>
        <a class="rc-sidebar-link {{ $active === 'analytics' ? 'active' : '' }}" href="{{ route('hr.analytics.reports') }}">{{ __('messages.analytics_reports') }}</a>
        <a class="rc-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}" href="{{ route('hr.notifications') }}">{{ __('messages.notifications') }} @if($notifCount > 0)<span class="rc-badge">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>@endif</a>
        <a class="rc-sidebar-link {{ $active === 'departments' ? 'active' : '' }}" href="{{ route('hr.departments') }}">{{ __('messages.departments') }}</a>
        <a class="rc-sidebar-link {{ $active === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">{{ __('messages.settings') }}</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="rc-sidebar-link w-full text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">{{ __('messages.logout') }}</button>
        </form>
    </nav>

    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 text-white font-bold flex items-center justify-center overflow-hidden">
                @if($avatar)
                    <img src="{{ $avatar }}" alt="{{ $user?->name }}" class="h-full w-full object-cover" />
                @else
                    {{ $initials }}
                @endif
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-sm text-white truncate">{{ $user?->name }}</div>
                <div class="text-xs text-white/70 truncate">{{ $user?->email }}</div>
            </div>
        </div>
    </div>
</aside>

<div id="rc-overlay"></div>

<div id="rc-main" class="min-h-screen flex flex-col transition-[margin] duration-200">
    <header class="h-16 sticky top-0 z-30 px-4 sm:px-6 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
        <button id="rc-menu-btn" class="h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 flex items-center justify-center" aria-label="Toggle menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <button id="rc-sidebar-toggle-desktop" class="hidden lg:inline-flex h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 items-center justify-center" aria-label="Toggle sidebar" aria-expanded="true">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <div class="hidden md:flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2 min-w-[18rem]">
            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="rc-quick-search" placeholder="{{ __('messages.quick_search_placeholder') }}" class="bg-transparent outline-none text-sm w-full text-slate-700 dark:text-slate-200 placeholder:text-muted" />
        </div>

        <div class="ml-auto flex items-center gap-2">
            @if(session('impersonator_id'))
                <form method="POST" action="{{ route('admin.impersonation.leave') }}">
                    @csrf
                    <button type="submit" class="h-10 rounded-xl border border-amber-300 bg-amber-50 px-3 text-xs font-semibold text-amber-700 hover:bg-amber-100">{{ __('messages.return_to_admin') }}</button>
                </form>
            @endif

            @include('partials.language-switcher')

            <button id="rc-theme-btn" class="h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800" aria-label="Toggle theme">
                <svg class="h-5 w-5 mx-auto dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                <svg class="h-5 w-5 mx-auto hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            </button>

            <a href="{{ route('hr.notifications') }}" class="relative h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 flex items-center justify-center" aria-label="Notifications">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($notifCount > 0)
                    <span class="absolute -top-1 -right-1 rc-badge">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
                @endif
            </a>

            <div class="relative" id="rc-profile-wrap">
                <button id="rc-profile-btn" type="button" class="h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 pl-1 pr-2 inline-flex items-center gap-2" aria-haspopup="menu" aria-expanded="false" aria-label="Open profile menu">
                    <span class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 text-white font-bold flex items-center justify-center overflow-hidden border border-blue-100 dark:border-blue-900">
                        @if($avatar)
                            <img src="{{ $avatar }}" alt="{{ $user?->name }}" class="h-full w-full object-cover" />
                        @else
                            {{ $initials }}
                        @endif
                    </span>
                    <span class="hidden sm:inline text-sm font-semibold text-slate-700 dark:text-slate-200 max-w-[8rem] truncate">{{ $user?->name }}</span>
                </button>

                <div id="rc-profile-menu" class="hidden absolute right-0 mt-2 w-64 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl p-3 z-50" role="menu">
                    <div class="px-2 py-1.5 border-b border-slate-100 dark:border-slate-800">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $user?->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $user?->email }}</p>
                    </div>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('settings') }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">{{ __('messages.profile_settings') }}</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="w-full text-left rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">{{ __('messages.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 px-4 sm:px-6 py-6">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70">
        <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between text-muted">
            <p>&copy; {{ now()->year }} Smart Recruitment. {{ __('messages.all_rights_reserved_sentence') }}</p>
            <p>{{ __('messages.recruiter_operations_workspace') }}</p>
        </div>
    </footer>
</div>

{{-- ── Chat Bot FAB ── --}}
<button id="chat-fab" aria-label="Open AI recruitment assistant">
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.862 9.862 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
</button>

<div id="chat-window" role="dialog" aria-label="AI Recruitment Assistant">
    <div class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-700 to-blue-800 text-white">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.07 4.93A10 10 0 114.93 19.07"/></svg>
        <span class="text-sm font-semibold">AI Recruitment Assistant</span>
        <button id="chat-close" type="button" class="ml-auto opacity-80 hover:opacity-100" aria-label="Close chat">✕</button>
    </div>
    <div id="chat-messages">
        <div class="bot-msg">👋 Hi {{ $user?->name }}! Ask me about your jobs, candidate pipeline, or AI recommendations.</div>
    </div>
    <input id="chat-input" type="text" placeholder="Ask something..." autocomplete="off" />
</div>

<script>
(function () {
    const sidebar = document.getElementById('rc-sidebar');
    const overlay = document.getElementById('rc-overlay');
    const menuBtn = document.getElementById('rc-menu-btn');
    const desktopToggle = document.getElementById('rc-sidebar-toggle-desktop');
    const themeBtn = document.getElementById('rc-theme-btn');
    const profileBtn = document.getElementById('rc-profile-btn');
    const profileMenu = document.getElementById('rc-profile-menu');
    const profileWrap = document.getElementById('rc-profile-wrap');
    const storageKey = 'recruiter-sidebar-collapsed';
    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    const openSidebar = () => { sidebar.classList.add('open'); overlay.classList.add('show'); };
    const closeSidebar = () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); };

    const setCollapsed = (collapsed) => {
        document.body.classList.toggle('rc-sidebar-collapsed', collapsed);
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
        desktopToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    menuBtn?.addEventListener('click', () => {
        if (isDesktop()) {
            setCollapsed(!document.body.classList.contains('rc-sidebar-collapsed'));
            return;
        }

        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    desktopToggle?.addEventListener('click', () => setCollapsed(!document.body.classList.contains('rc-sidebar-collapsed')));

    overlay?.addEventListener('click', closeSidebar);

    setCollapsed(localStorage.getItem(storageKey) === '1');

    themeBtn?.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('recruiter-theme', dark ? 'dark' : 'light');
    });

    profileBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        profileMenu?.classList.toggle('hidden');
        profileBtn.setAttribute('aria-expanded', profileMenu?.classList.contains('hidden') ? 'false' : 'true');
    });

    document.addEventListener('click', (event) => {
        if (profileWrap && profileMenu && !profileWrap.contains(event.target)) {
            profileMenu.classList.add('hidden');
            profileBtn?.setAttribute('aria-expanded', 'false');
        }
    });

    /* ── Chat bot ── */
    const chatFab    = document.getElementById('chat-fab');
    const chatWindow = document.getElementById('chat-window');
    const chatClose  = document.getElementById('chat-close');
    const chatInput  = document.getElementById('chat-input');
    const chatMsgs   = document.getElementById('chat-messages');

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
    const chatApiUrl = '{{ route('hr.chatbot.message') }}';

    const sendChatQuestion = async (question) => {
        addMsg(question, 'user');
        chatInput.value = '';

        const loading = document.createElement('div');
        loading.className = 'bot-msg bot-loading';
        loading.textContent = '⏳ Thinking...';
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
                addMsg('AI support could not generate an answer right now. Please try again.', 'bot');
                return;
            }

            addMsg(data.reply, 'bot');
        } catch (error) {
            loading.remove();
            addMsg('There was a problem reaching AI support. Please try again later.', 'bot');
            console.error(error);
        }
    };

    chatFab?.addEventListener('click', () => chatWindow?.classList.toggle('show'));
    chatClose?.addEventListener('click', () => chatWindow?.classList.remove('show'));
    chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && chatInput.value.trim()) {
            sendChatQuestion(chatInput.value.trim());
        }
    });
})();
</script>

@yield('scripts')
</body>
</html>
