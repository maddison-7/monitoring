@extends('layouts.recruiter')

@section('content')
@php
    $active = 'jobs.index';
    $statusLabels = [
        'draft' => __('messages.status_draft'),
        'published' => __('messages.status_published'),
        'closed' => __('messages.status_closed'),
    ];
@endphp
<div class="card p-5 mb-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" action="{{ route('hr.jobs.index') }}" class="grid gap-3 sm:grid-cols-3 lg:w-[70%]">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('messages.search_title_description') }}" class="rounded-xl border border-border px-3 py-2" />
            <select name="status" class="rounded-xl border border-border px-3 py-2">
                <option value="">{{ __('messages.all_status') }}</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">{{ __('messages.filter') }}</button>
        </form>

        <a href="{{ route('hr.jobs.create') }}" class="nav-btn nav-btn-primary px-4 py-2 text-sm">{{ __('messages.create_vacancy') }}</a>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($jobs as $job)
        <article class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-lg font-semibold text-text">{{ $job->title }}</h3>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $job->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($job->status === 'closed' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') }}">{{ $statusLabels[$job->status] ?? strtoupper((string) $job->status) }}</span>
            </div>

            <p class="mt-2 text-sm text-muted line-clamp-3">{{ $job->description }}</p>

            <dl class="mt-4 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('messages.department') }}</dt><dd class="font-medium">{{ $job->department?->name ?? __('messages.not_available') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('messages.experience') }}</dt><dd class="font-medium">{{ __('messages.experience_' . ($job->experience_level ?? 'mid')) }} ({{ $job->min_years_experience }}{{ __('messages.years_short') }})</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('messages.deadline') }}</dt><dd class="font-medium">{{ optional($job->application_deadline)->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('messages.positions') }}</dt><dd class="font-medium">{{ $job->positions }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('messages.applications') }}</dt><dd class="font-semibold">{{ $job->applications_count }}</dd></div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('hr.jobs.edit', $job) }}" class="nav-btn px-3 py-2 text-sm">{{ __('messages.edit') }}</a>
                <a href="{{ route('hr.candidate.ranking', ['job_id' => $job->id]) }}" class="nav-btn px-3 py-2 text-sm">{{ __('messages.view_candidates') }}</a>
                <form method="POST" action="{{ route('hr.jobs.delete', $job) }}" onsubmit="return confirm('{{ __('messages.delete_vacancy_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nav-btn px-3 py-2 text-sm text-red-600">{{ __('messages.delete') }}</button>
                </form>
            </div>
        </article>
    @empty
        <div class="card p-6 text-muted">{{ __('messages.no_vacancies_found') }}</div>
    @endforelse
</div>

<div class="mt-6">{{ $jobs->links() }}</div>
@endsection
