@extends('layouts.auth')

@php
    $pageTitle = 'Forgot Password';
    $hideTopNavAuthLinks = true;
    $eyebrow = 'Account recovery';
    $headline = 'Reset your password securely.';
    $intro = 'Enter your account email and we will send a password reset link.';
    $highlights = [
        ['title' => 'Works for all roles', 'description' => 'Applicants, recruiters, and admins can recover access from the same flow.'],
        ['title' => 'Secure reset token', 'description' => 'The reset link is time-limited and tied to your account email.'],
    ];
@endphp

@section('auth-card')
    <div class="mb-7">
        <div class="inline-flex rounded-full bg-blueSoft px-3 py-1 text-xs font-semibold text-blueDeep">Password recovery</div>
        <h2 class="mt-4 font-display text-3xl font-bold text-ink">Forgot password</h2>
        <p class="mt-2 text-sm text-slateSoft">We will email you a secure password reset link.</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blueDeep px-5 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:bg-blue-700">
            Send reset link
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slateSoft">
        Remembered your password?
        <a href="{{ route('login') }}" class="font-semibold text-blueDeep hover:underline">Back to login</a>
    </div>
@endsection
