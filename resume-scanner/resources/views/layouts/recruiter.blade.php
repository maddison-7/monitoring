<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle ?? 'Recruiter Panel' }} - Smart Recruitment</title>

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
                <p class="text-xs uppercase tracking-[0.18em] text-white/70">Recruiter Panel</p>
                <h1 class="font-bold text-white leading-tight">Smart Recruitment</h1>
            </div>
        </div>
    </div>

    <nav class="px-3 py-4 space-y-1 text-sm flex-1 overflow-y-auto">
        <p class="px-3 mb-2 text-[.65rem] uppercase tracking-widest text-white/60 font-semibold">Main Menu</p>
        <a class="rc-sidebar-link {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('hr.dashboard') }}">Dashboard</a>
        <a class="rc-sidebar-link {{ in_array($active, ['hr.jobs', 'jobs', 'jobs.index'], true) ? 'active' : '' }}" href="{{ route('hr.jobs.index') }}">Vacancy Management</a>
        <a class="rc-sidebar-link {{ in_array($active, ['hr.ranking', 'hr.candidate.ranking'], true) ? 'active' : '' }}" href="{{ route('hr.candidate.ranking') }}">Applicants</a>
        <a class="rc-sidebar-link {{ $active === 'interviews' ? 'active' : '' }}" href="{{ route('hr.interviews') }}">Interviews</a>
        <a class="rc-sidebar-link {{ $active === 'analytics' ? 'active' : '' }}" href="{{ route('hr.analytics.reports') }}">Analytics & Reports</a>
        <a class="rc-sidebar-link {{ $active === 'notifications' ? 'active' : '' }}" href="{{ route('hr.notifications') }}">Notifications @if($notifCount > 0)<span class="rc-badge">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>@endif</a>
        <a class="rc-sidebar-link {{ $active === 'departments' ? 'active' : '' }}" href="{{ route('hr.departments') }}">Departments</a>
        <a class="rc-sidebar-link {{ $active === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">Settings</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="rc-sidebar-link w-full text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">Logout</button>
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
            <input type="text" id="rc-quick-search" placeholder="Quick search candidates, jobs..." class="bg-transparent outline-none text-sm w-full text-slate-700 dark:text-slate-200 placeholder:text-muted" />
        </div>

        <div class="ml-auto flex items-center gap-2">
            @if(session('impersonator_id'))
                <form method="POST" action="{{ route('admin.impersonation.leave') }}">
                    @csrf
                    <button type="submit" class="h-10 rounded-xl border border-amber-300 bg-amber-50 px-3 text-xs font-semibold text-amber-700 hover:bg-amber-100">Return to Admin</button>
                </form>
            @endif

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
                        <a href="{{ route('settings') }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Profile & Settings</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="w-full text-left rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 px-4 sm:px-6 py-6">
        @yield('content')
    </main>

    <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70">
        <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between text-muted">
            <p>&copy; {{ now()->year }} Smart Recruitment. All rights reserved.</p>
            <p>Recruiter operations workspace.</p>
        </div>
    </footer>
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
})();
</script>

@yield('scripts')
</body>
</html>
