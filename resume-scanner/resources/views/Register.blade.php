@extends('layouts.auth')

@php
    $pageTitle = __('messages.register');
    $eyebrow = __('messages.create_account');
    $headline = __('messages.join_smart_recruitment');
    $intro = __('messages.create_your_account_intro');
    $highlights = [
        ['title' => __('messages.fast_onboarding'), 'description' => __('messages.fast_onboarding_desc')],
        ['title' => __('messages.role_ready_access'), 'description' => __('messages.role_ready_access_desc')],
        ['title' => __('messages.updated_workflows'), 'description' => __('messages.updated_workflows_desc')],
        ['title' => __('messages.consistent_interface'), 'description' => __('messages.consistent_interface_desc')],
    ];
@endphp

@section('auth-nav')
    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-slateSoft hover:bg-white hover:text-ink transition">{{ __('messages.login') }}</a>
    <a href="{{ route('register') }}" class="rounded-full px-4 py-2 bg-blueDeep text-white shadow-soft hover:bg-blue-700 transition">{{ __('messages.create_account') }}</a>
@endsection

@section('auth-card')
    <div class="mb-7">
        <div class="inline-flex rounded-full bg-blueSoft px-3 py-1 text-xs font-semibold text-blueDeep">{{ __('messages.new_account') }}</div>
        <h2 class="mt-4 font-display text-3xl font-bold text-ink">{{ __('messages.create_account') }}</h2>
        <p class="mt-2 text-sm text-slateSoft">{{ __('messages.create_your_account_intro') }}</p>
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
                <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.first_name') }}</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Maddison" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('first_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.last_name') }}</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Smith" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('last_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.email_address') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
            @error('email') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.role') }}</label>
            <select name="role" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white">
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>{{ __('messages.choose_role') }}</option>
                    @foreach (($roles ?? ['applicant']) as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>{{ str($role)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.password') }}</label>
                <input type="password" name="password" placeholder="••••••••" autocomplete="new-password" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
                @error('password') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink">{{ __('messages.confirm_password') }}</label>
                <input type="password" name="password_confirmation" placeholder="••••••••" autocomplete="new-password" required class="w-full rounded-2xl border border-line bg-paper px-4 py-3 text-sm text-ink outline-none transition focus:border-blueDeep focus:bg-white" />
            </div>
        </div>

        <div>
            <label class="flex items-start gap-3 text-sm text-slateSoft">
                <input type="checkbox" name="terms_accepted" value="1" required {{ old('terms_accepted') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-line text-blueDeep focus:ring-blueDeep" />
                <span>I have read and agree to the <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-blueDeep hover:underline">Terms and Conditions</a>.</span>
            </label>
            @error('terms_accepted') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" id="registerBtn" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blueDeep px-5 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:bg-blue-700">
            <span id="btnText">{{ __('messages.create_account') }}</span>
            <svg id="btnSpinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
                <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slateSoft">
        {{ __('messages.already_have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-blueDeep hover:underline">{{ __('messages.sign_in') }}</a>
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