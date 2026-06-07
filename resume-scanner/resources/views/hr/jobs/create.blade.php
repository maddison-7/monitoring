@extends('layouts.recruiter')

@section('content')
@php $active = 'jobs.index'; @endphp
<section class="card p-5">
    <div class="mb-5 flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-text">Create New Vacancy</h2>
        <a href="{{ route('hr.jobs.index') }}" class="nav-btn px-3 py-2 text-sm">Back to Jobs</a>
    </div>

    <form method="POST" action="{{ route('hr.jobs.store') }}" class="space-y-5">
        @csrf
        @include('hr.jobs._form')

        <div class="flex justify-end">
            <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">Create Vacancy</button>
        </div>
    </form>
</section>
@endsection
