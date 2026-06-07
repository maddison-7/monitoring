@extends('layouts.recruiter')

@section('content')
@php $active = 'jobs.index'; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Job setup</div>
                    <h2 class="mt-4 text-2xl font-bold text-text">{{ isset($editingJob) && $editingJob ? 'Edit job description' : 'Create job descriptions' }}</h2>
                    <p class="mt-2 text-sm text-muted">Recruiter can define the role, requirements, and scope.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Required *</span>
            </div>

            <form method="POST" action="{{ isset($editingJob) && $editingJob ? route('job.description.update', $editingJob->id) : route('job.description.store') }}" class="mt-6 space-y-5">
                @csrf
                @if (isset($editingJob) && $editingJob)
                    @method('PUT')
                @endif
                <div class="grid gap-5 md:grid-cols-2">
                    <input name="title" value="{{ old('title', $editingJob->title ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent" placeholder="Job title" />
                    <input name="department" value="{{ old('department', $editingJob->department ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent" placeholder="Department" />
                    <input name="location" value="{{ old('location', $editingJob->location ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent" placeholder="Location" />
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <textarea name="about" rows="4" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent md:col-span-2" placeholder="About the role">{{ old('about', $editingJob->about ?? '') }}</textarea>
                    <textarea name="responsibilities" rows="4" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent" placeholder="Responsibilities">{{ old('responsibilities', $editingJob->responsibilities ?? '') }}</textarea>
                    <textarea name="requirements" rows="4" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent" placeholder="Requirements">{{ old('requirements', $editingJob->requirements ?? '') }}</textarea>
                    <input name="skills" value="{{ old('skills', $editingJob->skills_text ?? '') }}" class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent md:col-span-2" placeholder="Skills separated by commas" />
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <select name="employment_type" class="rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent">
                        <option value="">Employment type</option>
                        <option value="full_time" @selected(old('employment_type', $editingJob->employment_type ?? '') === 'full_time')>Full-time</option>
                        <option value="part_time" @selected(old('employment_type', $editingJob->employment_type ?? '') === 'part_time')>Part-time</option>
                        <option value="contract" @selected(old('employment_type', $editingJob->employment_type ?? '') === 'contract')>Contract</option>
                        <option value="intern" @selected(old('employment_type', $editingJob->employment_type ?? '') === 'intern')>Intern</option>
                    </select>
                    <select name="seniority" class="rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent">
                        <option value="">Seniority</option>
                        <option value="junior" @selected(old('seniority', $editingJob->seniority ?? '') === 'junior')>Junior</option>
                        <option value="mid" @selected(old('seniority', $editingJob->seniority ?? '') === 'mid')>Mid</option>
                        <option value="senior" @selected(old('seniority', $editingJob->seniority ?? '') === 'senior')>Senior</option>
                        <option value="lead" @selected(old('seniority', $editingJob->seniority ?? '') === 'lead')>Lead</option>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button class="nav-btn-primary h-11 px-5 text-sm font-semibold">{{ isset($editingJob) && $editingJob ? 'Update job description' : 'Save job description' }}</button>
                    @if (isset($editingJob) && $editingJob)
                        <a href="{{ route('job.description') }}" class="inline-flex h-11 items-center rounded-xl border border-border bg-white px-5 text-sm font-semibold text-text hover:bg-slate-50 transition">Cancel edit</a>
                    @endif
                </div>
            </form>

            <div class="mt-8">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-text">Available job descriptions</h3>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ count($jobs ?? []) }} total</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-[0.14em] text-muted">
                            <tr>
                                <th class="py-3 pr-4">Title</th>
                                <th class="py-3 pr-4">Department</th>
                                <th class="py-3 pr-4">Seniority</th>
                                <th class="py-3 pr-4">Created</th>
                                <th class="py-3 pr-4">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($jobs ?? []) as $listedJob)
                                <tr class="border-t border-border">
                                    <td class="py-4 pr-4 font-semibold text-text">{{ (string) data_get($listedJob, 'title', 'Untitled') }}</td>
                                    <td class="py-4 pr-4 text-muted">{{ (string) data_get($listedJob, 'department', '') !== '' ? (string) data_get($listedJob, 'department') : 'N/A' }}</td>
                                    <td class="py-4 pr-4 text-muted">{{ (string) data_get($listedJob, 'seniority', '') !== '' ? (string) data_get($listedJob, 'seniority') : 'N/A' }}</td>
                                    <td class="py-4 pr-4 text-muted">{{ optional(data_get($listedJob, 'created_at'))->format('Y-m-d') }}</td>
                                    <td class="py-4 pr-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('job.description', ['edit_job_id' => (int) data_get($listedJob, 'id', 0)]) }}" class="inline-flex rounded-lg border border-accent bg-white px-3 py-1 text-xs font-semibold text-accent hover:bg-accent hover:text-white transition">Edit</a>
                                            <form method="POST" action="{{ route('job.description.delete', (int) data_get($listedJob, 'id', 0)) }}" onsubmit="return confirm('Delete this job description? Related candidate results will also be removed.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-border">
                                    <td colspan="5" class="py-6 text-sm text-muted">No job descriptions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-text">What this supports</h3>
                <p class="mt-2 text-sm text-muted">Supports job setup, matching logic, scoring, and ranking readiness.</p>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-text">Tip</h3>
                <p class="mt-2 text-sm text-muted">Keep the job description clear so scoring stays consistent.</p>
            </div>
        </aside>
    </div>
@endsection
