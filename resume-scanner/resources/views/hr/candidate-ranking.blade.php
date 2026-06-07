@extends('layouts.recruiter')

@section('content')
@php $active = 'hr.candidate.ranking'; @endphp
<div class="card p-5 mb-6">
    <form method="GET" action="{{ route('hr.candidate.ranking') }}" class="grid gap-3 md:grid-cols-5">
        <select name="job_id" class="rounded-xl border border-border px-3 py-2">
            <option value="">All Jobs</option>
            @foreach($jobs as $job)
                <option value="{{ $job->id }}" @selected((int)request('job_id', $selectedJobId) === (int)$job->id)>{{ $job->title }}</option>
            @endforeach
        </select>
        <input type="text" name="skill" value="{{ request('skill') }}" placeholder="Skill filter" class="rounded-xl border border-border px-3 py-2" />
        <input type="text" name="degree" value="{{ request('degree') }}" placeholder="Degree filter" class="rounded-xl border border-border px-3 py-2" />
        <input type="number" step="0.01" name="score_min" value="{{ request('score_min') }}" placeholder="Min score" class="rounded-xl border border-border px-3 py-2" />
        <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">Filter</button>
    </form>
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left">Candidate</th>
                <th class="px-4 py-3 text-left">Job</th>
                <th class="px-4 py-3 text-left">Score</th>
                <th class="px-4 py-3 text-left">Recommendation</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Applied</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                @php
                    $applicant = $application->applicant;
                    $skills = collect($applicant?->skills ?? [])->pluck('name')->filter()->values();
                    $educationLevels = collect($applicant?->educations ?? [])->pluck('level')->filter()->values();
                    $hasCv = !empty($applicant?->cv_path);
                    $experienceYears = collect($applicant?->experiences ?? [])->sum(function ($experience) {
                        $start = $experience->start_date ? \Illuminate\Support\Carbon::parse($experience->start_date) : null;
                        $end = ($experience->is_current ?? false)
                            ? now()
                            : ($experience->end_date ? \Illuminate\Support\Carbon::parse($experience->end_date) : now());

                        if (!$start) {
                            return 0;
                        }

                        return max($start->floatDiffInYears($end), 0);
                    });
                    $cvTextPreview = trim((string) ($applicant?->cv_text ?? ''));
                @endphp
                <tr class="border-t border-border">
                    <td class="px-4 py-3">
                        <div class="font-semibold">{{ $application->applicant?->user?->name ?? 'Unknown' }}</div>
                        <div class="text-xs text-muted">{{ $application->applicant?->user?->email }}</div>
                        <div class="mt-2 text-xs text-muted space-y-1">
                            <div>Phone: {{ $applicant?->phone ?: 'N/A' }}</div>
                            <div>Location: {{ trim(($applicant?->city ?: '') . ((($applicant?->city ?? '') && ($applicant?->country ?? '')) ? ', ' : '') . ($applicant?->country ?: '')) ?: 'N/A' }}</div>
                            <div>Experience: {{ number_format($experienceYears, 1) }} years</div>
                            <div>Education: {{ $educationLevels->take(2)->implode(', ') ?: 'N/A' }}</div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse($skills->take(6) as $skill)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700">{{ $skill }}</span>
                            @empty
                                <span class="text-[11px] text-muted">No skills listed</span>
                            @endforelse
                        </div>

                        @if($cvTextPreview !== '')
                            <details class="mt-2 text-xs text-slate-600">
                                <summary class="cursor-pointer font-medium text-slate-700">CV extracted summary</summary>
                                <div class="mt-1 rounded-lg border border-border bg-slate-50 p-2 leading-relaxed">
                                    {{ \Illuminate\Support\Str::limit($cvTextPreview, 450) }}
                                </div>
                            </details>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $application->job?->title ?? 'N/A' }}</td>
                    <td class="px-4 py-3 font-semibold">{{ number_format((float)($application->aiScore->match_percentage ?? 0), 2) }}%</td>
                    <td class="px-4 py-3">{{ $application->aiScore->recommendation_level ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ strtoupper($application->status) }}</td>
                    <td class="px-4 py-3">{{ optional($application->applied_at)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-wrap gap-2">
                                @if($hasCv)
                                    <a href="{{ route('hr.applications.cv', ['application' => $application, 'disposition' => 'inline']) }}" target="_blank" class="nav-btn px-3 py-1 text-xs">Preview CV</a>
                                    <a href="{{ route('hr.applications.cv', ['application' => $application, 'disposition' => 'download']) }}" class="nav-btn px-3 py-1 text-xs">Download CV</a>
                                @else
                                    <span class="text-xs text-amber-700">No CV uploaded</span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('hr.applications.shortlist', $application) }}">
                                    @csrf
                                    <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">Shortlist</button>
                                </form>

                                <form method="POST" action="{{ route('hr.applications.status', $application) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="status" value="review" />
                                    <button type="submit" class="nav-btn px-3 py-1 text-xs">Mark Review</button>
                                </form>

                                <form method="POST" action="{{ route('hr.applications.status', $application) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected" />
                                    <button type="submit" class="nav-btn px-3 py-1 text-xs">Reject</button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('hr.applications.interviews.schedule', $application) }}" class="grid gap-2 md:grid-cols-4">
                                @csrf
                                <input type="datetime-local" name="scheduled_at" class="rounded-xl border border-border px-3 py-2 text-xs" required />
                                <select name="mode" class="rounded-xl border border-border px-3 py-2 text-xs" required>
                                    <option value="online">Online</option>
                                    <option value="physical">Physical</option>
                                </select>
                                <input type="text" name="venue" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="Venue (if physical)" />
                                <input type="url" name="meeting_link" class="rounded-xl border border-border px-3 py-2 text-xs" placeholder="Meeting link (if online)" />
                                <label class="md:col-span-4 inline-flex items-center gap-2 text-xs text-muted">
                                    <input type="checkbox" name="send_email_invite" value="1" class="rounded border border-border" checked />
                                    Send interview invitation by email
                                </label>
                                <div class="md:col-span-4">
                                    <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">Schedule Interview</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('hr.applications.offer.send', $application) }}" class="grid gap-2 md:grid-cols-4">
                                @csrf
                                <input type="text" name="offer_message" class="rounded-xl border border-border px-3 py-2 text-xs md:col-span-3" placeholder="Offer message (optional)" />
                                <div class="flex items-center gap-2">
                                    <label class="inline-flex items-center gap-2 text-xs text-muted">
                                        <input type="checkbox" name="send_email_offer" value="1" class="rounded border border-border" checked />
                                        Email offer
                                    </label>
                                </div>
                                <div class="md:col-span-4">
                                    <button type="submit" class="nav-btn nav-btn-primary px-3 py-1 text-xs">Send Offer</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('hr.applications.onboarding.update', $application) }}" class="grid gap-2 md:grid-cols-4">
                                @csrf
                                <select name="onboarding_status" class="rounded-xl border border-border px-3 py-2 text-xs" required>
                                    <option value="not_started">Onboarding: Not Started</option>
                                    <option value="in_progress">Onboarding: In Progress</option>
                                    <option value="completed">Onboarding: Completed</option>
                                </select>
                                <input type="text" name="onboarding_notes" class="rounded-xl border border-border px-3 py-2 text-xs md:col-span-2" placeholder="Onboarding notes (optional)" />
                                <button type="submit" class="nav-btn px-3 py-1 text-xs">Update Onboarding</button>
                            </form>

                            <form method="POST" action="{{ route('hr.applications.placement.close', $application) }}" class="grid gap-2 md:grid-cols-4">
                                @csrf
                                <input type="text" name="placement_notes" class="rounded-xl border border-border px-3 py-2 text-xs md:col-span-3" placeholder="Placement closure notes (optional)" />
                                <button type="submit" class="nav-btn px-3 py-1 text-xs">Close Placement</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-muted">No candidates found for current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $applications->links() }}</div>
@endsection
