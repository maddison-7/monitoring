@php
    $supportedLocales = config('app.supported_locales', ['en' => 'English']);
    $currentLocale = app()->getLocale();
@endphp

<div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 text-xs font-semibold shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-label="{{ __('messages.language') }}">
    @foreach($supportedLocales as $localeKey => $localeName)
        <a
            href="{{ route('locale.switch', $localeKey) }}"
            class="inline-flex h-8 min-w-10 items-center justify-center rounded-lg px-2.5 transition {{ $currentLocale === $localeKey ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}"
            title="{{ $localeName }}"
            aria-current="{{ $currentLocale === $localeKey ? 'true' : 'false' }}"
        >
            {{ strtoupper($localeKey) }}
        </a>
    @endforeach
</div>
