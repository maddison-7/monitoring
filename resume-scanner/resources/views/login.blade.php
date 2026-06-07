@extends('layouts.auth')

@php
    $pageTitle = 'Login';
    $hideTopNavAuthLinks = true;
    $eyebrow = 'Welcome back';
    $headline = 'Sign in to your role workspace.';
    $intro = 'Applicant, Recruiter, and Admin each have different access and workflows. Use the same login form with your role account.';
    $highlights = [
        ['title' => 'Applicant login', 'description' => 'Apply for jobs, track your progress, and follow interview and offer updates.'],
        ['title' => 'Recruiter login', 'description' => 'Create vacancies, shortlist candidates, schedule interviews, and manage hiring stages.'],
        ['title' => 'Admin login', 'description' => 'Manage users, oversee system settings, governance, security, and audit operations.'],
    ];
@endphp

@section('auth-nav')
    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">Login</a>
    <a href="{{ route('register') }}" class="rounded-full px-4 py-2 bg-blueDeep text-white shadow-soft hover:bg-blue-700 transition">Create account</a>
@endsection

@section('auth-card')
    <div class="mb-7">
        <div class="inline-flex rounded-full bg-blueSoft px-3 py-1 text-xs font-semibold text-blueDeep">Account access</div>
        <h2 class="mt-4 font-display text-3xl font-bold text-ink">Sign in</h2>
        <p class="mt-2 text-sm text-slateSoft">Sign in using your Applicant, Recruiter, or Admin account credentials.</p>
    </div>

    @if ($errors->any() || session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() ?? session('error') ?? 'Invalid email or password.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" id="loginForm" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-ink">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                autocomplete="email"
                required
                class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white"
            />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-ink">Password</label>
            <div class="relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-2xl border border-line bg-paper px-4 py-3 pr-16 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white"
                />
                <button type="button" id="togglePwd" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-blueDeep">Show</button>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2 text-slateSoft">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} class="h-4 w-4 rounded border-line text-blueDeep focus:ring-blueDeep" />
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-blueDeep hover:underline">Forgot password?</a>
        </div>

        <button type="submit" id="loginBtn" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blueDeep px-5 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:bg-blue-700">
            <span id="btnText">Sign in</span>
            <svg id="btnSpinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
                <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slateSoft">
        New here?
        <a href="{{ route('register') }}" class="font-semibold text-blueDeep hover:underline">Create an account</a>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('togglePwd')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = 'Hide';
        } else {
            input.type = 'password';
            this.textContent = 'Show';
        }
    });

    document.getElementById('loginForm')?.addEventListener('submit', function () {
        const email = document.getElementById('email')?.value?.trim();
        const password = document.getElementById('password')?.value?.trim();
        if (!email || !password) return;

        document.getElementById('btnText')?.classList.add('hidden');
        document.getElementById('btnSpinner')?.classList.remove('hidden');
        document.getElementById('loginBtn')?.setAttribute('disabled', 'disabled');
    });
</script>
@endsection