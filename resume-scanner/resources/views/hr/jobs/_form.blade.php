@php
    $isEdit = isset($job);
    $experienceOptions = [
        'entry' => __('messages.experience_entry'),
        'junior' => __('messages.experience_junior'),
        'mid' => __('messages.experience_mid'),
        'senior' => __('messages.experience_senior'),
        'lead' => __('messages.experience_lead'),
        'executive' => __('messages.experience_executive'),
    ];

    $statusOptions = [
        'draft' => __('messages.status_draft'),
        'published' => __('messages.status_published'),
        'closed' => __('messages.status_closed'),
    ];
@endphp

<section class="mb-5 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="flex items-center gap-2 border-b border-slate-200 px-5 py-3 dark:border-slate-700">
        <svg class="h-5 w-5 text-slate-600 dark:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m7.78-14.78-.7.7M4.92 19.08l-.7.7M21 12h-1M4 12H3m16.08 7.08-.7-.7M5.62 5.62l-.7-.7"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15c0-1.2.7-2 2-2h2c1.3 0 2 .8 2 2"/>
            <circle cx="12" cy="9" r="2.4"/>
        </svg>
        <h3 class="text-base font-bold tracking-wide text-slate-800 dark:text-slate-100">{{ __('messages.vacancy_form_tips') }}</h3>
    </div>

    <div class="grid gap-4 p-5 md:grid-cols-2">
        <article class="rounded-xl bg-blue-100/80 p-4 dark:bg-blue-900/30">
            <div class="mb-2 flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-800 text-xs font-bold text-white">1</span>
                <h4 class="font-semibold text-slate-800 dark:text-slate-100">{{ __('messages.complete_required_fields') }}</h4>
            </div>
            <p class="text-sm leading-6 text-slate-700 dark:text-slate-200">{{ __('messages.complete_required_fields_help') }}</p>
        </article>

        <article class="rounded-xl bg-blue-100/80 p-4 dark:bg-blue-900/30">
            <div class="mb-2 flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-800 text-xs font-bold text-white">2</span>
                <h4 class="font-semibold text-slate-800 dark:text-slate-100">{{ __('messages.choose_department_status') }}</h4>
            </div>
            <p class="text-sm leading-6 text-slate-700 dark:text-slate-200">{{ __('messages.choose_department_status_help') }}</p>
        </article>
    </div>
</section>

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.job_title') }}</label>
        <input type="text" name="title" value="{{ old('title', $job->title ?? '') }}" class="w-full rounded-xl border border-border px-3 py-2" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.department') }}</label>
        <select name="department_id" class="w-full rounded-xl border border-border px-3 py-2">
            <option value="">{{ __('messages.select_department') }}</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string)old('department_id', $job->department_id ?? '') === (string)$department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.experience_level') }}</label>
        <select name="experience_level" class="w-full rounded-xl border border-border px-3 py-2" required>
            @foreach($experienceOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('experience_level', $job->experience_level ?? 'mid') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.min_years_experience') }}</label>
        <input type="number" min="0" max="50" name="min_years_experience" value="{{ old('min_years_experience', $job->min_years_experience ?? 0) }}" class="w-full rounded-xl border border-border px-3 py-2" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.application_deadline') }}</label>
        <input type="datetime-local" name="application_deadline" value="{{ old('application_deadline', isset($job) && $job->application_deadline ? $job->application_deadline->format('Y-m-d\\TH:i') : '') }}" class="w-full rounded-xl border border-border px-3 py-2" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.number_of_positions') }}</label>
        <input type="number" min="1" max="999" name="positions" value="{{ old('positions', $job->positions ?? 1) }}" class="w-full rounded-xl border border-border px-3 py-2" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.status') }}</label>
        <select name="status" class="w-full rounded-xl border border-border px-3 py-2" required>
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $job->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.qualifications') }}</label>
        <textarea name="qualifications" rows="3" class="w-full rounded-xl border border-border px-3 py-2" placeholder="{{ __('messages.qualifications_placeholder') }}">{{ old('qualifications', $job->qualifications ?? '') }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.duties') }}</label>
        <textarea name="duties" rows="4" class="w-full rounded-xl border border-border px-3 py-2" placeholder="{{ __('messages.duties_placeholder') }}">{{ old('duties', $job->duties ?? '') }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.required_skills_csv') }}</label>
        <input type="text" name="required_skills_csv" value="{{ old('required_skills_csv', isset($job) ? implode(', ', (array)($job->required_skills_json ?? [])) : '') }}" class="w-full rounded-xl border border-border px-3 py-2" placeholder="Laravel, REST API, MySQL" />
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-muted mb-1">{{ __('messages.job_description') }}</label>
        <textarea name="description" rows="7" class="w-full rounded-xl border border-border px-3 py-2" required>{{ old('description', $job->description ?? '') }}</textarea>
    </div>
</div>
