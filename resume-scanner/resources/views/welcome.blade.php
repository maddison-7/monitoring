<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Recruitment - Modern Hiring Workspace</title>
    <meta name="description" content="Smart Recruitment connects applicant and recruiter portals with modern dashboards, analytics, notifications, and workflow automation.">

    <script>
        (function () {
            const stored = localStorage.getItem('welcome-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        display: ['Manrope', 'sans-serif'],
                    },
                    boxShadow: {
                        glow: '0 28px 70px rgba(29, 78, 216, 0.22)',
                        soft: '0 16px 38px rgba(15, 23, 42, 0.10)',
                    },
                },
            },
        };
    </script>

    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --brand: #1d4ed8;
            --brand-strong: #1e3a8a;
            --paper: #ffffff;
        }

        body {
            background:
                radial-gradient(circle at 12% 10%, rgba(14, 165, 233, 0.28), transparent 36%),
                radial-gradient(circle at 88% 20%, rgba(59, 130, 246, 0.22), transparent 34%),
                radial-gradient(circle at 50% 88%, rgba(16, 185, 129, 0.16), transparent 30%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2FF 44%, #F8FAFC 100%);
            color: var(--ink);
        }

        html.dark body {
            background:
                radial-gradient(circle at 12% 10%, rgba(14, 165, 233, 0.14), transparent 36%),
                radial-gradient(circle at 88% 20%, rgba(59, 130, 246, 0.12), transparent 34%),
                radial-gradient(circle at 50% 88%, rgba(16, 185, 129, 0.08), transparent 30%),
                linear-gradient(180deg, #020617 0%, #0B1220 52%, #020617 100%);
            color: #E2E8F0;
        }

        .glass {
            backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.78);
        }

        html.dark .glass {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(51, 65, 85, 0.9) !important;
        }

        .hero-grid {
            background-image: linear-gradient(to right, rgba(59, 130, 246, 0.12) 1px, transparent 1px), linear-gradient(to bottom, rgba(59, 130, 246, 0.12) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        html.dark .hero-grid {
            background-image: linear-gradient(to right, rgba(59, 130, 246, 0.18) 1px, transparent 1px), linear-gradient(to bottom, rgba(59, 130, 246, 0.18) 1px, transparent 1px);
        }

        html.dark .bg-white,
        html.dark .bg-white\/70,
        html.dark .bg-slate-50 {
            background-color: #0F172A !important;
        }

        html.dark .border-white\/70,
        html.dark .border-slate-100,
        html.dark .border-slate-200,
        html.dark .border-blue-100 {
            border-color: #334155 !important;
        }

        html.dark .text-slate-900 {
            color: #E2E8F0 !important;
        }

        html.dark .text-slate-700,
        html.dark .text-slate-600 {
            color: #94A3B8 !important;
        }

        html.dark .hover\:bg-white:hover,
        html.dark .hover\:bg-slate-50:hover,
        html.dark .hover\:bg-slate-100:hover {
            background-color: #1E293B !important;
        }

        .hero-orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(22px);
            opacity: .5;
            pointer-events: none;
        }

        .float-soft {
            animation: float-soft 6s ease-in-out infinite;
        }

        .fade-up {
            animation: fade-up .65s ease both;
        }

        .fade-up-delay {
            animation: fade-up .85s ease both;
        }

        @keyframes float-soft {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes fade-up {
            from {
                transform: translateY(14px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="text-slate-900 antialiased">
    <header class="sticky top-0 z-50 px-4 py-4 sm:px-8">
        <div class="mx-auto w-full max-w-7xl rounded-2xl border border-white/70 glass shadow-soft">
            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl bg-blue-700 text-white font-display text-lg font-extrabold grid place-items-center">SR</div>
                <div>
                    <p class="font-display text-lg font-bold leading-tight">Smart Recruitment</p>
                    <p class="text-xs text-slate-600">Applicant + Recruiter Portals</p>
                </div>
            </a>

            <nav class="hidden items-center gap-2 md:flex">
                <a href="#dashboards" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-white">Dashboards</a>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <button id="themeToggleBtn" type="button" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" aria-label="Toggle theme">
                    <span id="themeToggleLabel">Dark</span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white shadow-glow hover:bg-blue-800">Open Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Login</a>
                    <a href="{{ route('register') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white shadow-glow hover:bg-blue-800">Register</a>
                @endauth
            </div>

                <button id="mobileMenuBtn" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 md:hidden" aria-label="Toggle menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>

            <div id="mobileMenu" class="hidden border-t border-slate-100 px-4 pb-4 pt-3 md:hidden sm:px-6">
                <div class="grid gap-2">
                    <button id="themeToggleBtnMobile" type="button" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" aria-label="Toggle theme">
                        <span id="themeToggleLabelMobile">Dark</span>
                    </button>
                    <a href="#dashboards" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Dashboards</a>

                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white shadow-glow hover:bg-blue-800">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Login</a>
                            <a href="{{ route('register') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white shadow-glow hover:bg-blue-800">Register</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 pb-12 sm:px-8 sm:pb-16">
        <section class="relative mx-auto mt-8 max-w-7xl overflow-hidden rounded-3xl border border-blue-100 bg-white p-6 shadow-soft sm:p-10 dark:bg-slate-900">
            <div class="grid gap-8 lg:grid-cols-[1.1fr_.9fr] lg:items-center">
                <div class="fade-up">
                    <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">
                        <span class="h-2 w-2 rounded-full bg-blue-700"></span>
                        Apply Today
                    </div>
                    <h1 class="mt-5 font-display text-4xl font-extrabold leading-[1.05] text-slate-900 sm:text-5xl lg:text-6xl">
                        Welcome Applicants, find jobs and apply with confidence.
                    </h1>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                        Explore open vacancies, submit your application in minutes, and track each step from screening to interview in one clear portal.
                    </p>

                    <div class="mt-7 flex flex-wrap gap-2 text-xs font-semibold text-slate-700 sm:text-sm">
                        <span class="rounded-full border border-blue-100 bg-white px-3 py-1">Role-based portals</span>
                        <span class="rounded-full border border-blue-100 bg-white px-3 py-1">AI-assisted screening</span>
                        <span class="rounded-full border border-blue-100 bg-white px-3 py-1">Live recruitment analytics</span>
                    </div>
                </div>

                <div class="fade-up-delay">
                    <article class="relative overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-soft">
                        <img
                            src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80"
                            alt="Recruitment planning and collaboration board"
                            class="h-[320px] w-full object-cover sm:h-[360px]"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 via-slate-900/10 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-100">Smart Hiring Workspace</p>
                            <h3 class="mt-2 font-display text-2xl font-bold">Modern workflows built for speed and clarity</h3>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="showcase" class="mx-auto mt-10 max-w-7xl rounded-3xl border border-slate-200 bg-white p-4 shadow-soft sm:p-6">
            <div class="grid gap-4 lg:grid-cols-[1.15fr_.85fr]">
                <article class="relative overflow-hidden rounded-2xl border border-slate-200">
                    <img
                        src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1600&q=80"
                        alt="Modern recruitment team reviewing hiring analytics on screens"
                        class="h-[340px] w-full object-cover sm:h-[420px]"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 via-slate-900/10 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-5 text-white sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Career Journey</p>
                        <h2 class="mt-2 font-display text-2xl font-bold sm:text-3xl">Discover opportunities and submit your next application</h2>
                    </div>
                </article>

                <div class="grid gap-4">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-700">Admin Control</p>
                        <h3 class="mt-2 font-display text-xl font-bold">System Controller dashboard</h3>
                        <p class="mt-2 text-sm text-slate-600">Centralized governance, user control, and recruitment analytics without clutter.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Recruiter Workspace</p>
                        <h3 class="mt-2 font-display text-xl font-bold">Stronger candidate lifecycle actions</h3>
                        <p class="mt-2 text-sm text-slate-600">Offer, acceptance, onboarding, and placement closure in one clear flow.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-700">Applicant Portal</p>
                        <h3 class="mt-2 font-display text-xl font-bold">Cleaner progress visibility</h3>
                        <p class="mt-2 text-sm text-slate-600">Better profile flow, notifications, interview status, and personalized recommendations.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="features" class="mx-auto mt-10 max-w-7xl">
            <div class="relative overflow-hidden rounded-3xl border border-blue-100 shadow-soft">
                <img
                    src="https://images.unsplash.com/photo-1497215842964-222b430dc094?auto=format&fit=crop&w=1800&q=80"
                    alt="Modern office workspace with soft blur"
                    class="h-[260px] w-full object-cover sm:h-[320px] lg:h-[360px]"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-slate-900/55 via-blue-900/35 to-cyan-800/30 backdrop-blur-[2px]"></div>
                <div class="absolute inset-x-0 bottom-0 p-6 text-white sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-100">Smart Recruitment</p>
                    <h3 class="mt-2 font-display text-2xl font-bold sm:text-3xl">A cleaner, more focused hiring experience</h3>
                </div>
            </div>
        </section>

        <section id="dashboards" class="mx-auto mt-10 max-w-7xl rounded-3xl bg-gradient-to-r from-slate-900 to-blue-950 px-6 py-8 text-white sm:px-10">
            <h2 class="font-display text-3xl font-bold sm:text-4xl">Ready to enter your updated portal?</h2>
            <p class="mt-3 max-w-3xl text-sm text-blue-100 sm:text-base">Use role-aware pages, improved navigation, and modern visual reporting to manage the full hiring cycle with clarity.</p>
        </section>
    </main>

    <footer class="px-4 pb-8 sm:px-8 sm:pb-10">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/70 px-4 py-3 text-sm text-slate-600 glass sm:px-6">
            <p>Smart Recruitment &copy; {{ date('Y') }}. Built for modern hiring teams.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('terms') }}" class="hover:text-slate-900">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-slate-900">Privacy</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const themeBtn = document.getElementById('themeToggleBtn');
            const themeBtnMobile = document.getElementById('themeToggleBtnMobile');
            const themeLabel = document.getElementById('themeToggleLabel');
            const themeLabelMobile = document.getElementById('themeToggleLabelMobile');

            const syncThemeLabels = () => {
                const dark = document.documentElement.classList.contains('dark');
                const nextLabel = dark ? 'Light' : 'Dark';
                if (themeLabel) themeLabel.textContent = nextLabel;
                if (themeLabelMobile) themeLabelMobile.textContent = nextLabel;
            };

            const toggleTheme = () => {
                const dark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('welcome-theme', dark ? 'dark' : 'light');
                syncThemeLabels();
            };

            mobileMenuBtn?.addEventListener('click', () => {
                mobileMenu?.classList.toggle('hidden');
            });

            themeBtn?.addEventListener('click', toggleTheme);
            themeBtnMobile?.addEventListener('click', toggleTheme);

            syncThemeLabels();
        })();
    </script>
</body>
</html>
