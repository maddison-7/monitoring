<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Auth' }} - Smart Recruitment</title>

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Manrope', 'sans-serif'],
                    },
                    colors: {
                        ink: '#0F172A',
                        slateSoft: '#475569',
                        line: '#E2E8F0',
                        paper: '#F8FAFC',
                        blueSoft: '#DBEAFE',
                        blueDeep: '#1D4ED8',
                        goldSoft: '#FEF3C7',
                    },
                    boxShadow: {
                        soft: '0 24px 60px rgba(15, 23, 42, 0.10)',
                    },
                    keyframes: {
                        rise: {
                            '0%': { opacity: '0', transform: 'translateY(16px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                    animation: {
                        rise: 'rise .6s ease both',
                    },
                },
            },
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F3F6FB;
        }

        html.dark body {
            background: #0B1120;
            color: #E2E8F0;
        }

        html.dark .bg-white,
        html.dark .bg-paper,
        html.dark .bg-slate-100,
        html.dark .bg-slate-50 {
            background-color: #0F172A !important;
            border-color: #1E293B !important;
        }

        html.dark .text-ink,
        html.dark .text-slate-600,
        html.dark .text-slate-700 {
            color: #E2E8F0 !important;
        }

        html.dark .text-slateSoft {
            color: #94A3B8 !important;
        }

        html.dark .text-blueDeep {
            color: #93C5FD !important;
        }

        html.dark .bg-blueSoft {
            background-color: #1E3A8A !important;
        }

        html.dark .border-line,
        html.dark .border,
        html.dark .border-white\/70,
        html.dark .border-white\/80,
        html.dark .border-blueSoft {
            border-color: #1E293B !important;
        }

        html.dark input,
        html.dark select,
        html.dark textarea {
            background: #111827 !important;
            color: #E2E8F0 !important;
            border-color: #334155 !important;
        }
    </style>
</head>
<body class="min-h-screen text-ink">
    <div class="relative min-h-screen overflow-hidden">
        <header class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-6">
            <div class="rounded-3xl border border-white/70 bg-white/80 backdrop-blur px-5 py-4 shadow-soft flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-blueDeep text-white flex items-center justify-center font-extrabold">RS</div>
                    <div>
                        <div class="font-display text-lg font-bold">Smart Recruitment</div>
                        <div class="text-sm text-slateSoft">Applicant and recruiter portals</div>
                    </div>
                </a>

                <nav class="hidden sm:flex items-center gap-3 text-sm font-medium">
                    @unless (!empty($hideTopNavAuthLinks))
                        <a href="{{ route('home') }}#features" class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">Features</a>
                        <a href="{{ route('home') }}#workflow" class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">Workflow</a>
                    @endunless
                    @include('partials.language-switcher')
                    <button type="button" data-theme-toggle class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">Dark</button>
                    @unless (!empty($hideTopNavAuthLinks))
                        @yield('auth-nav')
                    @endunless
                </nav>
            </div>

            @unless (!empty($hideTopNavAuthLinks))
                <div class="mt-3 sm:hidden grid grid-cols-2 gap-2 text-sm font-medium">
                    <a href="{{ route('home') }}#features" class="rounded-xl border border-line bg-white px-3 py-2 text-center text-slateSoft">Features</a>
                    <a href="{{ route('home') }}#workflow" class="rounded-xl border border-line bg-white px-3 py-2 text-center text-slateSoft">Workflow</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-line bg-white px-3 py-2 text-center text-slateSoft">Login</a>
                    <div class="col-span-2 flex justify-center">
                        @include('partials.language-switcher')
                    </div>
                    <button type="button" data-theme-toggle class="rounded-xl border border-line bg-white px-3 py-2 text-slateSoft">Dark</button>
                </div>
            @else
                <div class="mt-3 sm:hidden grid grid-cols-1 gap-2 text-sm font-medium">
                    <div class="flex justify-center">
                        @include('partials.language-switcher')
                    </div>
                    <button type="button" data-theme-toggle class="rounded-xl border border-line bg-white px-3 py-2 text-slateSoft">Dark</button>
                </div>
            @endunless
        </header>

        <main class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14 lg:py-16">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_.95fr] items-center min-h-[calc(100vh-10rem)]">
                <section class="max-w-2xl animate-rise">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blueSoft bg-white px-4 py-2 text-sm font-semibold text-blueDeep shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-blueDeep"></span>
                        {{ $eyebrow ?? 'Welcome back' }}
                    </div>

                    <h1 class="mt-6 font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.04]">
                        {{ $headline ?? 'A clear start for every hiring task.' }}
                    </h1>

                    @if (!empty($intro))
                        <p class="mt-6 max-w-xl text-lg leading-8 text-slateSoft">
                            {{ $intro }}
                        </p>
                    @endif

                    @if (!empty($highlights))
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            @foreach ($highlights as $highlight)
                                <div class="rounded-3xl border border-line bg-white px-5 py-5 shadow-sm">
                                    <div class="font-semibold text-ink">{{ $highlight['title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-slateSoft">{{ $highlight['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="animate-rise" style="animation-delay: .06s;">
                    <div class="rounded-[2rem] border border-white/80 bg-white shadow-soft p-6 sm:p-8 lg:p-9">
                        @yield('auth-card')
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        const themeToggles = document.querySelectorAll('[data-theme-toggle]');

        const syncThemeLabel = () => {
            const label = document.documentElement.classList.contains('dark') ? 'Light' : 'Dark';
            themeToggles.forEach((toggle) => {
                toggle.textContent = label;
            });
        };

        themeToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
                syncThemeLabel();
            });
        });

        syncThemeLabel();
    </script>

    @yield('scripts')
</body>
</html>
