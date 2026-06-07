<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ranked Candidates — Resume Screener</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        syne: ['Syne', 'sans-serif'],
                        dm: ['DM Sans', 'sans-serif'],
                    },
                    colors: {
                        ink:    '#0B1020',
                        dusk:   '#111A2E',
                        card:   'rgba(255,255,255,0.06)',
                        stroke: 'rgba(255,255,255,0.10)',
                        muted:  'rgba(255,255,255,0.68)',
                        soft:   'rgba(255,255,255,0.82)',

                        copper: '#D07A3A',
                        ember:  '#FF4D6D',
                        sage:   '#4FD1B5',
                        gold:   '#F2C14E',
                    },
                    keyframes: {
                        driftA: {
                            '0%': { transform: 'translate(0,0) scale(1)' },
                            '100%': { transform: 'translate(-50px,70px) scale(1.08)' }
                        },
                        driftB: {
                            '0%': { transform: 'translate(0,0) scale(1)' },
                            '100%': { transform: 'translate(65px,-55px) scale(1.12)' }
                        },
                        fadeUp: {
                            from: { opacity: '0', transform: 'translateY(18px)' },
                            to: { opacity: '1', transform: 'translateY(0)' }
                        }
                    },
                    animation: {
                        driftA: 'driftA 16s ease-in-out infinite alternate',
                        driftB: 'driftB 20s ease-in-out infinite alternate',
                        fadein: 'fadeUp .6s ease both',
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'DM Sans', sans-serif; }

        .bg-root{
            background:
                radial-gradient(900px 600px at 10% 15%, rgba(208,122,58,0.18), transparent 60%),
                radial-gradient(900px 650px at 85% 30%, rgba(79,209,181,0.14), transparent 62%),
                radial-gradient(800px 600px at 70% 95%, rgba(255,77,109,0.10), transparent 60%),
                linear-gradient(180deg, #0B1020 0%, #0A0F1C 60%, #070B14 100%);
        }

        .glass{
            background: linear-gradient(180deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.05) 100%);
            border: 1px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(16px);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03) inset,
                0 20px 60px rgba(0,0,0,0.45);
        }

        .nav-link{
            display:flex;
            align-items:center;
            gap:.7rem;
            padding:.9rem 1rem;
            border-radius: 1rem;
            color: rgba(255,255,255,0.72);
            transition: all .2s ease;
            border: 1px solid transparent;
        }
        .nav-link:hover{ background: rgba(255,255,255,0.06); color:#fff; }
        .nav-link.active{
            background: rgba(242,193,78,0.10);
            border-color: rgba(242,193,78,0.22);
            color: #F2C14E;
        }

        .badge{
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.74);
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .stat{
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .chip{
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10);
        }

        .highlight{
            background: linear-gradient(90deg, #F2C14E 0%, #D07A3A 40%, #4FD1B5 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .table-row{
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .table-row:hover{
            background: rgba(255,255,255,0.04);
        }

        .pill-pass{
            background: rgba(79,209,181,0.12);
            border: 1px solid rgba(79,209,181,0.22);
            color: rgba(195,255,245,0.92);
        }
        .pill-mid{
            background: rgba(242,193,78,0.10);
            border: 1px solid rgba(242,193,78,0.22);
            color: rgba(255,244,214,0.95);
        }
        .pill-low{
            background: rgba(255,77,109,0.10);
            border: 1px solid rgba(255,77,109,0.22);
            color: rgba(255,204,212,0.95);
        }

        select option{
            background: #0B1020;
            color: rgba(255,255,255,0.92);
        }

        .layout-shell {
            height: 100vh;
        }

        #sidebarPanel {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 40;
            width: 320px;
            max-width: 85vw;
            transform: translateX(-100%);
            transition: transform .25s ease, opacity .2s ease;
            background: rgba(11,16,32,0.96);
        }

        #sidebarBackdrop {
            position: fixed;
            inset: 0;
            z-index: 30;
            background: rgba(5, 8, 16, 0.62);
        }

        .sidebar-open #sidebarPanel {
            transform: translateX(0);
        }

        @media (min-width: 1024px) {
            .layout-shell {
                display: grid;
                grid-template-columns: 320px 1fr;
                transition: grid-template-columns .25s ease;
            }

            .layout-shell.sidebar-closed {
                grid-template-columns: 0 1fr;
            }

            #sidebarPanel {
                position: relative;
                z-index: 1;
                width: auto;
                max-width: none;
                transform: translateX(0);
                background: transparent;
            }

            .layout-shell.sidebar-closed #sidebarPanel {
                opacity: 0;
                pointer-events: none;
                transform: translateX(-10px);
            }

            #sidebarBackdrop {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-root text-white min-h-screen overflow-hidden">
@php
    // Demo data
    $candidates = [
        ['rank'=>1,'name'=>'Candidate A','email'=>'hidden@anonymized','skills'=>['Laravel','MySQL','REST APIs'],'exp'=>'2 years','score'=>92,'rec'=>'Shortlist','pill'=>'pill-pass'],
        ['rank'=>2,'name'=>'Candidate B','email'=>'hidden@anonymized','skills'=>['JavaScript','Tailwind','UI/UX'],'exp'=>'1.5 years','score'=>84,'rec'=>'Shortlist','pill'=>'pill-pass'],
        ['rank'=>3,'name'=>'Candidate C','email'=>'hidden@anonymized','skills'=>['Python','NLP','Prompting'],'exp'=>'1 year','score'=>73,'rec'=>'Consider','pill'=>'pill-mid'],
        ['rank'=>4,'name'=>'Candidate D','email'=>'hidden@anonymized','skills'=>['Networking','Linux','Security'],'exp'=>'1 year','score'=>66,'rec'=>'Consider','pill'=>'pill-mid'],
        ['rank'=>5,'name'=>'Candidate E','email'=>'hidden@anonymized','skills'=>['MS Office','Documentation'],'exp'=>'6 months','score'=>52,'rec'=>'Reject','pill'=>'pill-low'],
    ];

    // Same safe width mapping (no inline styles)
    $barClass = function (int $score): string {
        $score = max(0, min(100, $score));
        if ($score >= 95) return 'w-full';
        if ($score >= 85) return 'w-11/12';
        if ($score >= 75) return 'w-10/12';
        if ($score >= 65) return 'w-9/12';
        if ($score >= 55) return 'w-8/12';
        if ($score >= 45) return 'w-7/12';
        if ($score >= 35) return 'w-6/12';
        if ($score >= 25) return 'w-5/12';
        if ($score >= 15) return 'w-4/12';
        if ($score >= 5)  return 'w-3/12';
        return 'w-0';
    };
@endphp

<div id="layoutShell" class="layout-shell">

    <button id="sidebarBackdrop" class="hidden" type="button" aria-label="Close sidebar"></button>

    <!-- SIDEBAR (same as Dashboard) -->
    <aside id="sidebarPanel" class="border-r border-white/10 p-8 overflow-y-auto">
        <div class="flex justify-end mb-5 lg:hidden">
            <button id="sidebarClose" type="button" class="rounded-xl px-3 py-2 border border-white/15 bg-white/5 text-sm font-semibold hover:bg-white/10 transition">
                Close
            </button>
        </div>

        <div class="flex items-center gap-3 mb-12">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-copper to-ember flex items-center justify-center font-bold text-white shadow-xl">
                RS
            </div>

            <div>
                <h2 class="font-syne font-bold text-lg">Resume Screener</h2>
                <p class="text-sm text-muted">Recruitment Workspace</p>
            </div>
        </div>

        <nav class="space-y-2">
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
            <a href="{{ route('settings') }}" class="nav-link">Settings</a>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="mt-10">
            @csrf
            <button type="submit"
                    class="w-full rounded-xl py-3 border border-white/10 bg-white/5 hover:bg-white/10 transition text-sm font-semibold">
                Logout
            </button>
        </form>

        @auth
            <div class="mt-10 glass rounded-2xl p-4">
                <div class="text-xs uppercase tracking-[0.24em] text-muted">Signed in as</div>
                <div class="mt-2 font-semibold text-soft">{{ auth()->user()->name }}</div>
                <div class="text-sm text-muted">{{ auth()->user()->email }}</div>
            </div>
        @endauth
    </aside>

    <!-- MAIN -->
    <main class="overflow-y-auto p-8 lg:p-12">
        <!-- Header -->
        <div class="flex items-start justify-between gap-6 mb-8">
            <div>
                <button id="sidebarToggle" type="button" class="glass rounded-xl px-4 py-2 text-sm font-semibold hover:bg-white/10 transition mb-4" aria-expanded="false" aria-controls="sidebarPanel">
                    Toggle Sidebar
                </button>

                <p class="text-xs uppercase tracking-[0.24em] text-muted mb-3">Candidate matching</p>

                <h1 class="font-syne font-extrabold text-4xl leading-tight">
                    Ranked
                    <span class="highlight">Candidates</span>
                </h1>

                <p class="text-muted mt-4 max-w-2xl">
                    Candidates are ranked based on the current job description and scoring rules.
                    Replace the demo data with live results after you connect your upload + AI scoring modules.
                </p>
            </div>

            <div class="hidden md:flex gap-3">
                <a href="{{ route('hr.jobs.index') }}"
                   class="glass rounded-xl px-4 py-2 text-sm font-semibold hover:bg-white/10 transition">
                    Vacancy Management
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="glass rounded-3xl p-0 overflow-hidden">
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-syne text-xl font-extrabold">Results</h2>
                <div class="text-sm text-muted">
                    Showing <span class="text-soft font-semibold">{{ count($candidates) }}</span> candidates (demo)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-muted">
                            <th class="px-6 py-4 font-semibold">Rank</th>
                            <th class="px-6 py-4 font-semibold">Candidate</th>
                            <th class="px-6 py-4 font-semibold">Top skills</th>
                            <th class="px-6 py-4 font-semibold">Experience</th>
                            <th class="px-6 py-4 font-semibold">Score</th>
                            <th class="px-6 py-4 font-semibold">Recommendation</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="text-sm">
                        @foreach ($candidates as $c)
                            @php($w = $barClass((int) $c['score']))
                            <tr class="table-row">
                                <td class="px-6 py-4 text-soft font-semibold">#{{ $c['rank'] }}</td>

                                <td class="px-6 py-4">
                                    <div class="font-semibold text-white">{{ $c['name'] }}</div>
                                    <div class="text-xs text-muted mt-1">{{ $c['email'] }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($c['skills'] as $skill)
                                            <span class="chip rounded-full px-3 py-1 text-xs text-soft">{{ $skill }}</span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-soft">{{ $c['exp'] }}</td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-28 h-2 rounded-full bg-white/10 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-sage via-gold to-ember {{ $w }}"></div>
                                        </div>
                                        <div class="font-semibold text-soft">{{ $c['score'] }}%</div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $c['pill'] }}">
                                        {{ $c['rec'] }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="stat rounded-xl px-3 py-2 text-xs font-semibold hover:bg-white/10 transition">
                                            View
                                        </button>
                                        <button type="button" class="stat rounded-xl px-3 py-2 text-xs font-semibold hover:bg-white/10 transition">
                                            Export
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-5 border-t border-white/10">
                <p class="text-xs text-muted">
                    Note: Anonymization can be applied before scoring to reduce bias (planned).
                </p>
            </div>
        </div>
    </main>
</div>

<script>
    (function () {
        const layoutShell = document.getElementById('layoutShell');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const desktopQuery = window.matchMedia('(min-width: 1024px)');
        const STORAGE_KEY = 'dashboard-sidebar-open';

        if (!layoutShell || !sidebarToggle || !sidebarBackdrop) {
            return;
        }

        const savedState = localStorage.getItem(STORAGE_KEY);
        let isSidebarOpen = savedState === null ? desktopQuery.matches : savedState === '1';

        const syncSidebarState = () => {
            const isDesktop = desktopQuery.matches;

            layoutShell.classList.toggle('sidebar-open', isSidebarOpen);
            layoutShell.classList.toggle('sidebar-closed', isDesktop && !isSidebarOpen);

            sidebarBackdrop.classList.toggle('hidden', isDesktop || !isSidebarOpen);
            sidebarToggle.setAttribute('aria-expanded', isSidebarOpen ? 'true' : 'false');
            sidebarToggle.textContent = isSidebarOpen ? 'Close Sidebar' : 'Open Sidebar';
        };

        const setSidebarOpen = (open) => {
            isSidebarOpen = open;
            localStorage.setItem(STORAGE_KEY, open ? '1' : '0');
            syncSidebarState();
        };

        sidebarToggle.addEventListener('click', () => setSidebarOpen(!isSidebarOpen));
        sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));

        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => setSidebarOpen(false));
        }

        desktopQuery.addEventListener('change', () => {
            if (desktopQuery.matches && localStorage.getItem(STORAGE_KEY) === null) {
                isSidebarOpen = true;
            }
            syncSidebarState();
        });

        syncSidebarState();
    })();
</script>
</body>
</html>