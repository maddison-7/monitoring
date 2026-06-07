@extends('layouts.applicant')
@php $activeNav = 'applicant.jobs'; @endphp

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Application Form</p>
            <h2 class="mt-2 text-2xl font-semibold text-text">Apply for {{ $job->title }}</h2>
            <p class="mt-2 text-sm text-muted">Submit your application details below. The vacancy requirements were already shown on the previous page.</p>
        </div>

        @if($alreadyApplied)
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                You have already applied for this vacancy.
            </div>
        @else
            <form method="POST" action="{{ route('applicant.jobs.apply.submit', $job) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Cover Letter (text)</label>
                    <textarea name="cover_letter_text" rows="6" class="w-full rounded-xl border border-border px-3 py-2" placeholder="Write your cover letter..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('applicant.jobs.department', $job->department_id) }}" class="nav-btn px-4 py-2">Back</a>
                    <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">Submit Application</button>
                </div>
            </form>
        @endif
    </section>

    <section class="card p-5">
        <h3 class="font-semibold mb-3">Profile Readiness</h3>
        <ul class="space-y-2 text-sm">
            <li class="flex justify-between"><span class="text-muted">CV Uploaded</span><span class="font-semibold">{{ $applicant->cv_path ? 'Yes' : 'No' }}</span></li>
            <li class="flex justify-between"><span class="text-muted">Skills</span><span class="font-semibold">{{ $applicant->skills()->count() }}</span></li>
            <li class="flex justify-between"><span class="text-muted">Education</span><span class="font-semibold">{{ $applicant->educations()->count() }}</span></li>
            <li class="flex justify-between"><span class="text-muted">Experience</span><span class="font-semibold">{{ $applicant->experiences()->count() }}</span></li>
        </ul>
        <a href="{{ route('applicant.profile') }}" class="mt-4 inline-flex nav-btn px-3 py-2 text-sm">Update Profile</a>
    </section>
</div>
@endsection
