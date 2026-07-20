@extends('layouts.recruiter')

@section('content')
@php $active = 'jobs.index'; @endphp
<section class="card p-5">
    <div class="mb-5 flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-text">{{ __('messages.edit_vacancy') }}</h2>
        <a href="{{ route('hr.jobs.index') }}" class="nav-btn px-3 py-2 text-sm">{{ __('messages.back_to_jobs') }}</a>
    </div>

    <form method="POST" action="{{ route('hr.jobs.update', $job) }}" class="space-y-5">
        @csrf
        @method('PUT')
        @include('hr.jobs._form', ['job' => $job])

        <div class="flex justify-end gap-2">
            <a href="{{ route('hr.jobs.index') }}" class="nav-btn px-4 py-2">{{ __('messages.cancel') }}</a>
            <button type="submit" class="nav-btn nav-btn-primary px-4 py-2">{{ __('messages.update_vacancy') }}</button>
        </div>
    </form>
</section>
@endsection
