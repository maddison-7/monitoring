@extends('layouts.applicant')
@php $activeNav = 'applicant.profile'; @endphp

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <h2 class="text-lg font-semibold text-text mb-4">Personal Details</h2>
        <form method="POST" action="{{ route('applicant.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $applicant->phone) }}" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($applicant->date_of_birth)->format('Y-m-d')) }}" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Gender</label>
                    <select name="gender" class="w-full rounded-xl border border-border px-3 py-2">
                        <option value="">Choose</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender', $applicant->gender) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">National ID</label>
                    <input type="text" name="national_id" value="{{ old('national_id', $applicant->national_id) }}" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', $applicant->city) }}" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Country</label>
                    <input type="text" name="country" value="{{ old('country', $applicant->country) }}" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted mb-1">Address</label>
                <input type="text" name="address" value="{{ old('address', $applicant->address) }}" class="w-full rounded-xl border border-border px-3 py-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-muted mb-1">Languages</label>
                <input type="text" name="languages_csv" value="{{ old('languages_csv', $applicant->languages->pluck('language')->implode(', ')) }}" placeholder="e.g English, Swahili" class="w-full rounded-xl border border-border px-3 py-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-muted mb-1">Skills (comma separated)</label>
                <input type="text" name="skills" value="{{ old('skills', $applicant->skills->pluck('name')->implode(', ')) }}" class="w-full rounded-xl border border-border px-3 py-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-muted mb-1">Bio</label>
                <textarea name="bio" rows="4" class="w-full rounded-xl border border-border px-3 py-2">{{ old('bio', $applicant->bio) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted mb-1">GPA (0.00 - 4.00 scale)</label>
                <input type="number" step="0.01" min="0" max="4" name="gpa" value="{{ old('gpa', $applicant->gpa) }}" class="w-full rounded-xl border border-border px-3 py-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">CV (PDF/DOCX)</label>
                    <input type="file" name="cv" accept=".pdf,.doc,.docx" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-muted mb-1">Cover Letter (PDF/DOCX)</label>
                    <input type="file" name="cover_letter" accept=".pdf,.doc,.docx" class="w-full rounded-xl border border-border px-3 py-2" />
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">Save Profile</button>
            </div>
        </form>
    </section>

    <section class="card p-5">
        <h2 class="text-lg font-semibold text-text mb-4">Quick Summary</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-muted">Education Records</dt><dd class="font-semibold">{{ $applicant->educations->count() }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Experience Records</dt><dd class="font-semibold">{{ $applicant->experiences->count() }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Certifications</dt><dd class="font-semibold">{{ $applicant->certificates->count() }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Skills</dt><dd class="font-semibold">{{ $applicant->skills->count() }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">GPA</dt><dd class="font-semibold">{{ $applicant->gpa ?? 'N/A' }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">CV Uploaded</dt><dd class="font-semibold">{{ $applicant->cv_path ? 'Yes' : 'No' }}</dd></div>
        </dl>

        <div class="mt-6">
            <h3 class="font-semibold mb-2">Recent Applications</h3>
            <ul class="space-y-2 text-sm">
                @forelse($applicant->applications->take(5) as $application)
                    <li class="rounded-xl border border-border px-3 py-2">
                        <div class="font-medium">{{ $application->job?->title ?? 'Unknown Job' }}</div>
                        <div class="text-xs text-muted">{{ strtoupper($application->status) }} · {{ optional($application->applied_at)->format('Y-m-d') }}</div>
                    </li>
                @empty
                    <li class="text-muted">No applications yet.</li>
                @endforelse
            </ul>
        </div>
    </section>
</div>
@endsection
