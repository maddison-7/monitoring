<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle ?? __('messages.admin_panel') }} - Intelligent Recruitment System</title>

    <script>
        (function () {
            const stored = localStorage.getItem('admin-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        adminBg: '#F1F5F9',
                        adminPanel: '#FFFFFF',
                        adminBorder: '#E2E8F0',
                        adminText: '#0F172A',
                        adminMuted: '#64748B',
                        adminPrimary: '#1D4ED8',
                        adminPrimarySoft: '#DBEAFE',
                    },
                    boxShadow: {
                        admin: '0 18px 40px rgba(15, 23, 42, 0.08)',
                    },
                },
            },
        };
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
            color: #0F172A;
        }

        html.dark body {
            background: #020617;
            color: #E2E8F0;
        }

        #adminSidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 290px;
            z-index: 50;
            background: linear-gradient(180deg, #0F172A 0%, #1E3A8A 52%, #312E81 100%);
            border-right: 1px solid #E2E8F0;
            transform: translateX(-100%);
            transition: transform .22s ease;
            display: flex;
            flex-direction: column;
            color: #E2E8F0;
        }

        html.dark #adminSidebar {
            background: linear-gradient(180deg, #020617 0%, #0F172A 55%, #1E1B4B 100%);
            border-color: #1E293B;
        }

        #adminSidebar.open {
            transform: translateX(0);
        }

        body.admin-sidebar-collapsed #adminSidebar {
            transform: translateX(-100%);
        }

        body.admin-sidebar-collapsed #adminMain {
            margin-left: 0;
        }

        #adminOverlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
            z-index: 40;
            display: none;
        }

        #adminOverlay.show {
            display: block;
        }

        @media (min-width: 1024px) {
            #adminSidebar {
                transform: translateX(0);
            }

            #adminMain {
                margin-left: 290px;
            }

            body.admin-sidebar-collapsed #adminSidebar {
                transform: translateX(-100%);
            }

            body.admin-sidebar-collapsed #adminMain {
                margin-left: 0;
            }
        }

        .admin-link {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: .75rem 1rem;
            border-radius: .9rem;
            color: rgba(226, 232, 240, 0.86);
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s ease;
        }

        .admin-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #FFFFFF;
        }

        .admin-link.active {
            background: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
            font-weight: 600;
        }

        html.dark .admin-link { color: rgba(226, 232, 240, 0.86); }
        html.dark .admin-link:hover { background: rgba(255, 255, 255, 0.12); color: #FFFFFF; }
        html.dark .admin-link.active { background: rgba(255, 255, 255, 0.18); color: #FFFFFF; }

        .admin-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
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
            background: #1D4ED8;
            border-color: #1D4ED8;
            color: #FFFFFF;
        }

        .nav-btn-primary:hover {
            background: #2563EB;
            border-color: #2563EB;
        }

        .page-title {
            letter-spacing: -0.03em;
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

        html.dark .admin-card {
            background: #0F172A;
            border-color: #1E293B;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.46);
        }

        html.dark .card {
            background: #0F172A;
            border-color: #1E293B;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.42);
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
            background: #1D4ED8;
            border-color: #1D4ED8;
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

        .admin-stat {
            background: linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(248, 250, 252, 1));
        }

        html.dark .admin-stat {
            background: linear-gradient(180deg, rgba(15, 23, 42, 1), rgba(15, 23, 42, 0.94));
        }

        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
        }

        .pill-green { background: #D1FAE5; color: #065F46; }
        .pill-blue { background: #DBEAFE; color: #1D4ED8; }
        .pill-amber { background: #FEF3C7; color: #92400E; }
        .pill-violet { background: #EDE9FE; color: #6D28D9; }

        html.dark .pill-green { background: #064E3B40; color: #6EE7B7; }
        html.dark .pill-blue { background: #1E3A8A40; color: #93C5FD; }
        html.dark .pill-amber { background: #92400E40; color: #FCD34D; }
        html.dark .pill-violet { background: #4C1D9540; color: #C4B5FD; }

        .admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: .85rem;
            border: 1px solid #BFDBFE;
            background: #EFF6FF;
            color: #1D4ED8;
            padding: .55rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .15s ease;
        }

        .admin-btn:hover {
            background: #DBEAFE;
        }

        .admin-btn-primary {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: #FFFFFF;
            border-color: #1D4ED8;
        }

        .admin-btn-primary:hover {
            filter: brightness(.95);
        }

        .admin-table th {
            background: #F8FAFC;
            color: #64748B;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        html.dark .admin-table th {
            background: #0F172A;
            color: #64748B;
        }

        html.dark .admin-table td {
            border-color: #1E293B;
        }

        .status-dot {
            width: .65rem;
            height: .65rem;
            border-radius: 999px;
            background: #22C55E;
            box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.12);
        }
    </style>
</head>
<body class="h-full">
@php
    $user = auth()->user();
    $active = $activeNav ?? 'dashboard';
    $avatar = $user?->getAvatarUrl();
    $initials = $user?->getInitials() ?? 'AD';
    $notifCount = (int) ($dashboard['unreadNotifications'] ?? \App\Models\Notification::query()
        ->where('user_id', (int) ($user?->id ?? 0))
        ->whereNull('read_at')
        ->count());
    $systemStatus = collect($dashboard['systemHealth'] ?? [])->firstWhere('label', 'Server Status')['value'] ?? 'Online';
@endphp

<div id="adminRoutes" class="hidden"
     data-recruiters-route="{{ route('admin.recruiters') }}"
     data-reports-route="{{ route('admin.reports') }}"
     data-audit-route="{{ route('admin.audit') }}"
     data-jobs-route="{{ route('admin.jobs') }}"
     data-dashboard-route="{{ route('panel.admin') }}"
     data-settings-route="{{ route('admin.system') }}"></div>

<aside id="adminSidebar" aria-label="Admin navigation">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-700 text-white font-bold flex items-center justify-center">IR</div>
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-white/70">{{ __('messages.admin_panel') }}</p>
                <h1 class="font-bold text-white leading-tight">Intelligent Recruitment System</h1>
            </div>
        </div>
    </div>

    <nav class="px-3 py-4 space-y-1 flex-1 overflow-y-auto">
        <p class="px-3 mb-2 text-[.66rem] uppercase tracking-widest text-white/60 font-semibold">{{ __('messages.admin_panel') }}</p>
        <a href="{{ route('panel.admin') }}" class="admin-link {{ $active === 'dashboard' ? 'active' : '' }}">{{ __('messages.dashboard') }}</a>
        <a href="{{ route('admin.recruiters') }}" class="admin-link {{ $active === 'admin.recruiters' ? 'active' : '' }}">{{ __('messages.user_management') }}</a>
        <a href="{{ route('admin.governance') }}" class="admin-link {{ $active === 'admin.governance' ? 'active' : '' }}">{{ __('messages.governance') }}</a>
        <a href="{{ route('admin.jobs') }}" class="admin-link {{ $active === 'admin.jobs' ? 'active' : '' }}">{{ __('messages.organization_management') }}</a>
        <a href="{{ route('admin.analytics') }}" class="admin-link {{ $active === 'admin.analytics' ? 'active' : '' }}">{{ __('messages.recruitment_analytics') }}</a>
        <a href="{{ route('admin.audit') }}" class="admin-link {{ $active === 'admin.audit' ? 'active' : '' }}">{{ __('messages.audit') }}</a>
        <a href="{{ route('admin.reports') }}" class="admin-link {{ $active === 'admin.reports' ? 'active' : '' }}">{{ __('messages.reports') }}</a>
        <a href="{{ route('admin.system') }}" class="admin-link {{ $active === 'admin.system' ? 'active' : '' }}">{{ __('messages.system_settings') }}</a>
        <a href="{{ route('settings') }}" class="admin-link">{{ __('messages.settings') }}</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="admin-link w-full text-left text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">{{ __('messages.logout') }}</button>
        </form>
    </nav>

    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-500 to-indigo-700 text-white font-bold flex items-center justify-center overflow-hidden flex-shrink-0">
                @if($avatar)
                    <img src="{{ $avatar }}" class="h-full w-full object-cover" alt="{{ $user?->name }}" />
                @else
                    {{ $initials }}
                @endif
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-sm text-white truncate">{{ $user?->name }}</div>
                <div class="text-xs text-white/70 truncate">{{ $user?->email }}</div>
                <div class="mt-1 inline-flex items-center gap-2 text-[11px] font-semibold text-emerald-300">
                    <span class="status-dot"></span>
                    {{ $systemStatus }}
                </div>
            </div>
        </div>
    </div>
</aside>

<div id="adminOverlay"></div>

<div id="adminMain" class="min-h-screen flex flex-col transition-[margin] duration-200">
    <header class="sticky top-0 z-30 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-3">
            <button id="adminMenuBtn" type="button" class="admin-btn h-10 w-10 p-0 lg:hidden" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <button id="adminSidebarToggleDesktop" type="button" class="admin-btn h-10 w-10 p-0 hidden lg:inline-flex" aria-label="Toggle sidebar" aria-expanded="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <div class="hidden md:flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2 min-w-[20rem]">
                <svg class="h-4 w-4 text-adminMuted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
                <input id="adminGlobalSearch" type="text" placeholder="{{ __('messages.global_search_placeholder') }}" class="w-full bg-transparent outline-none text-sm text-slate-700 dark:text-slate-200 placeholder:text-adminMuted" />
            </div>

            <div class="ml-auto flex items-center gap-2">
                <div class="hidden sm:flex items-center gap-2 rounded-full px-3 py-1.5 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <span class="status-dot"></span>
                    {{ __('messages.system_status') }}: {{ $systemStatus }}
                </div>

                @include('partials.language-switcher')

                <button id="adminThemeBtn" type="button" class="admin-btn h-10 w-10 p-0" aria-label="Toggle theme">
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </button>

                <button id="adminNotifBtn" type="button" class="admin-btn h-10 w-10 p-0 relative" aria-label="Notifications">
                    <svg class="h-5 w-5 text-slate-600 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($notifCount > 0)
                        <span class="absolute -top-1 -right-1 min-w-4 h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
                    @endif
                </button>

                <div class="relative" id="adminProfileMenuWrap">
                    <button id="adminProfileMenuBtn" type="button" class="h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 pl-1 pr-2 inline-flex items-center gap-2" aria-haspopup="menu" aria-expanded="false" aria-label="Open profile menu">
                        <span class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-700 text-white font-bold flex items-center justify-center overflow-hidden border border-blue-100 dark:border-blue-900">
                            @if($avatar)
                                <img src="{{ $avatar }}" class="h-full w-full object-cover" alt="{{ $user?->name }}" />
                            @else
                                {{ $initials }}
                            @endif
                        </span>
                        <span class="hidden sm:inline text-sm font-semibold text-slate-700 dark:text-slate-200 max-w-[8rem] truncate">{{ $user?->name }}</span>
                    </button>

                    <div id="adminProfileMenu" class="hidden absolute right-0 mt-2 w-64 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl p-3 z-50" role="menu">
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
        </div>
    </header>

    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        @yield('content')
    </main>

    <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70">
        <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between text-adminMuted">
            <p>&copy; {{ now()->year }} Intelligent Recruitment System. {{ __('messages.all_rights_reserved_sentence') }}</p>
            <p>{{ __('messages.admin_control_center') }}</p>
        </div>
    </footer>
</div>

<script>
(function () {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    const menuBtn = document.getElementById('adminMenuBtn');
    const desktopToggle = document.getElementById('adminSidebarToggleDesktop');
    const themeBtn = document.getElementById('adminThemeBtn');
    const profileMenuBtn = document.getElementById('adminProfileMenuBtn');
    const profileMenu = document.getElementById('adminProfileMenu');
    const profileMenuWrap = document.getElementById('adminProfileMenuWrap');
    const search = document.getElementById('adminGlobalSearch');
    const routesEl = document.getElementById('adminRoutes');
    const routes = routesEl ? routesEl.dataset : {};
    const storageKey = 'admin-sidebar-collapsed';

    const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

    const navigate = (url) => {
        if (!url) return;
        window.location.href = url;
    };

    const openSidebar = () => {
        sidebar?.classList.add('open');
        overlay?.classList.add('show');
    };

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('show');
    };

    const setCollapsed = (collapsed) => {
        document.body.classList.toggle('admin-sidebar-collapsed', collapsed);
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
        desktopToggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    const toggleDesktopSidebar = () => setCollapsed(!document.body.classList.contains('admin-sidebar-collapsed'));

    menuBtn?.addEventListener('click', () => {
        if (isDesktop()) {
            toggleDesktopSidebar();
            return;
        }

        if (sidebar?.classList.contains('open')) {
            closeSidebar();
            return;
        }

        openSidebar();
    });

    desktopToggle?.addEventListener('click', toggleDesktopSidebar);

    overlay?.addEventListener('click', closeSidebar);

    setCollapsed(localStorage.getItem(storageKey) === '1');

    themeBtn?.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('admin-theme', dark ? 'dark' : 'light');
    });

    profileMenuBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        profileMenu?.classList.toggle('hidden');
        profileMenuBtn.setAttribute('aria-expanded', profileMenu?.classList.contains('hidden') ? 'false' : 'true');
    });

    document.addEventListener('click', (event) => {
        if (profileMenuWrap && profileMenu && !profileMenuWrap.contains(event.target)) {
            profileMenu.classList.add('hidden');
            profileMenuBtn?.setAttribute('aria-expanded', 'false');
        }
    });

    search?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;

        const value = (search.value || '').toLowerCase().trim();
        if (!value) return;

        if (value.includes('user') || value.includes('recruiter')) {
            navigate(routes.recruitersRoute);
            return;
        }

        if (value.includes('interview')) {
            navigate(routes.interviewsRoute);
            return;
        }

        if (value.includes('backup') || value.includes('database')) {
            navigate(routes.backupRoute);
            return;
        }

        if (value.includes('setting') || value.includes('config')) {
            navigate(routes.settingsRoute);
            return;
        }

        if (value.includes('report')) {
            navigate(routes.reportsRoute);
            return;
        }

        if (value.includes('audit') || value.includes('security')) {
            navigate(routes.auditRoute);
            return;
        }

        if (value.includes('job') || value.includes('vacancy') || value.includes('organization')) {
            if (value.includes('vacancy')) {
                navigate(routes.vacanciesRoute);
                return;
            }

            navigate(routes.jobsRoute);
            return;
        }

        navigate(routes.dashboardRoute);
    });
})();
</script>

@yield('scripts')
</body>
</html>
