@extends('layouts.auth')

@php
    $pageTitle = 'Register';
    $eyebrow = 'Create your account';
    $headline = 'Join the new Smart Recruitment system.';
    $intro = 'Create your account as an applicant and access the updated dashboard with independent feature pages.';
    $highlights = [
        ['title' => 'Fast onboarding', 'description' => 'Create a new account in a few guided steps.'],
        ['title' => 'Role-ready access', 'description' => 'Start as applicant with the correct portal automatically.'],
        ['title' => 'Updated workflows', 'description' => 'Work with vacancies, applications, interviews, and notifications seamlessly.'],
        ['title' => 'Consistent interface', 'description' => 'Welcome, login, and register now share one modern visual language.'],
    ];
@endphp

@section('auth-nav')
    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">Login</a>
    <a href="{{ route('register') }}" class="rounded-full px-4 py-2 bg-blueDeep text-white shadow-soft hover:bg-blue-700 transition">Create account</a>
@endsection

@section('auth-card')
    <div class="mb-7">
        <div class="inline-flex rounded-full bg-blueSoft px-3 py-1 text-xs font-semibold text-blueDeep">New account</div>
        <h2 class="mt-4 font-display text-3xl font-bold text-ink">Create account</h2>
        <p class="mt-2 text-sm text-slateSoft">Fill in your details to start using the updated system.</p>
    </div>

    @if ($errors->any() || session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() ?? session('error') ?? 'Please fix the errors and try again.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.submit') }}" id="registerForm" class="space-y-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-ink">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Maddison" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('first_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Smith" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('last_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink">Email address</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
            @error('email') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink">Role</label>
            <select name="role" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white">
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Choose role...</option>
                    @foreach (($roles ?? ['applicant']) as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ str($role)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-ink">Password</label>
                <input type="password" name="password" placeholder="••••••••" autocomplete="new-password" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('password') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink">Confirm password</label>
                <input type="password" name="password_confirmation" placeholder="••••••••" autocomplete="new-password" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
            </div>
        </div>

        <button type="submit" id="registerBtn" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blueDeep px-5 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:bg-blue-700">
            <span id="btnText">Create account</span>
            <svg id="btnSpinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
                <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slateSoft">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-blueDeep hover:underline">Sign in</a>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('registerForm')?.addEventListener('submit', function () {
        document.getElementById('btnText')?.classList.add('hidden');
        document.getElementById('btnSpinner')?.classList.remove('hidden');
        document.getElementById('registerBtn')?.setAttribute('disabled', 'disabled');
    });
</script>
@endsection