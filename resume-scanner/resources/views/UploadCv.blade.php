@extends('layouts.recruiter')

@section('content')
@php $active = 'jobs.index'; @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="inline-flex rounded-full bg-accentSoft px-3 py-1 text-xs font-semibold text-accent uppercase tracking-[0.16em]">Upload</div>
                    <h2 class="mt-4 text-2xl font-bold text-text">Upload candidate CVs</h2>
                    <p class="mt-2 text-sm text-muted">Bulk upload CVs in one go. Accepted formats: PDF, DOC, DOCX.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Max {{ $maxUploadFiles ?? 30 }} files</span>
            </div>

            <form id="bulk-upload-form" method="POST" action="{{ route('cv.upload') }}" enctype="multipart/form-data" class="mt-6 space-y-5" data-async-url="{{ route('cv.upload.async') }}" data-ranked-url="{{ route('ranked.candidates') }}">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-text mb-2">Job position</label>
                    @if ($jobs->isEmpty())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            No saved jobs yet. Create a job description first.
                        </div>
                    @else
                        <select name="job_id" required class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm outline-none focus:border-accent">
                            <option value="">Choose a job</option>
                            @foreach($jobs as $job)
                                <option value="{{ $job->id }}" @selected(old('job_id') == $job->id)>
                                    {{ $job->title }}@if(!empty($job->department)) - {{ $job->department }}@endif
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-text mb-2">Resume files</label>
                    <div id="drop-zone" class="w-full rounded-xl border border-dashed border-border bg-slate-50 px-4 py-6 text-sm text-center text-muted cursor-pointer hover:bg-slate-100 transition">
                        Drag and drop CV files here, or click to browse.
                    </div>
                    <input id="resume-files" type="file" name="resumes[]" multiple accept=".pdf,.doc,.docx" class="sr-only" />
                    <p id="resume-files-count" class="mt-2 text-xs text-muted">No files selected yet.</p>
                    <p class="mt-1 text-xs text-muted">Up to {{ $maxUploadFiles ?? 30 }} files, max {{ $maxUploadFileSizeMb ?? 5 }} MB each.</p>
                    <div id="file-progress-list" class="mt-3 space-y-2"></div>
                </div>

                <button id="bulk-upload-submit" class="nav-btn-primary h-11 px-5 text-sm font-semibold" @disabled($jobs->isEmpty())>Upload and continue</button>
            </form>
        </section>

        <aside class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-text">Upload notes</h3>
                <ul class="mt-3 space-y-3 text-sm text-muted leading-6">
                    <li>• Keep filenames simple and readable.</li>
                    <li>• Upload one job's CVs together in bulk.</li>
                    <li>• PDF is preferred for stable parsing.</li>
                </ul>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-text">Functional fit</h3>
                <p class="mt-2 text-sm text-muted">Supports upload, parsing, scoring, and ranking flow in the screening process.</p>
            </div>
        </aside>
    </div>

    <script>
        (function () {
            const form = document.getElementById('bulk-upload-form');
            const fileInput = document.getElementById('resume-files');
            const dropZone = document.getElementById('drop-zone');
            const countLabel = document.getElementById('resume-files-count');
            const progressList = document.getElementById('file-progress-list');
            const submitButton = document.getElementById('bulk-upload-submit');
            const maxFiles = Number('{{ (int) ($maxUploadFiles ?? 30) }}');

            if (!form || !fileInput || !dropZone || !countLabel || !progressList || !submitButton) {
                return;
            }

            let selectedFiles = [];

            const createProgressRow = function (fileName) {
                const row = document.createElement('div');
                row.className = 'rounded-xl border border-border bg-white px-3 py-2';
                row.innerHTML = '<div class="flex items-center justify-between gap-3 text-xs"><span class="text-text font-medium truncate" title="' + fileName + '">' + fileName + '</span><span class="status text-muted">Queued</span></div><div class="mt-2 h-2 w-full rounded-full bg-slate-100 overflow-hidden"><div class="bar h-full w-0 rounded-full bg-accent transition-all"></div></div>';

                return row;
            };

            const renderSelectedFiles = function () {
                progressList.innerHTML = '';

                if (selectedFiles.length === 0) {
                    countLabel.textContent = 'No files selected yet.';

                    return;
                }

                countLabel.textContent = selectedFiles.length + ' file(s) selected for bulk upload.';
                selectedFiles.forEach(function (file) {
                    progressList.appendChild(createProgressRow(file.name));
                });
            };

            const mergeFiles = function (incomingFiles) {
                const map = new Map(selectedFiles.map(function (file) {
                    return [file.name + ':' + file.size + ':' + file.lastModified, file];
                }));

                incomingFiles.forEach(function (file) {
                    const key = file.name + ':' + file.size + ':' + file.lastModified;
                    if (!map.has(key) && map.size < maxFiles) {
                        map.set(key, file);
                    }
                });

                selectedFiles = Array.from(map.values());
                renderSelectedFiles();
            };

            dropZone.addEventListener('click', function () {
                fileInput.click();
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropZone.classList.add('border-accent', 'bg-accentSoft');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropZone.classList.remove('border-accent', 'bg-accentSoft');
                });
            });

            dropZone.addEventListener('drop', function (event) {
                const dropped = Array.from(event.dataTransfer?.files || []);
                mergeFiles(dropped);
            });

            fileInput.addEventListener('change', function () {
                const picked = Array.from(fileInput.files || []);
                mergeFiles(picked);
                fileInput.value = '';
            });

            const uploadSingleFile = function (file, jobId, token, asyncUrl, row) {
                return new Promise(function (resolve) {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData();

                    formData.append('_token', token);
                    formData.append('job_id', jobId);
                    formData.append('resume', file);

                    const bar = row.querySelector('.bar');
                    const status = row.querySelector('.status');

                    xhr.open('POST', asyncUrl, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.addEventListener('progress', function (event) {
                        if (!event.lengthComputable) {
                            return;
                        }

                        const percent = Math.max(0, Math.min(100, Math.round((event.loaded / event.total) * 100)));
                        bar.style.width = percent + '%';
                        status.textContent = 'Uploading ' + percent + '%';
                    });

                    xhr.addEventListener('load', function () {
                        let payload = null;
                        try {
                            payload = JSON.parse(xhr.responseText || '{}');
                        } catch (error) {
                            payload = null;
                        }

                        if (xhr.status >= 200 && xhr.status < 300 && payload) {
                            const state = payload.status || 'failed';
                            if (state === 'processed') {
                                bar.style.width = '100%';
                                bar.classList.remove('bg-accent');
                                bar.classList.add('bg-emerald-500');
                                status.textContent = 'Processed';
                            } else if (state === 'duplicate') {
                                bar.style.width = '100%';
                                bar.classList.remove('bg-accent');
                                bar.classList.add('bg-amber-500');
                                status.textContent = 'Duplicate skipped';
                            } else {
                                bar.classList.remove('bg-accent');
                                bar.classList.add('bg-rose-500');
                                status.textContent = 'Failed';
                            }

                            resolve(payload);

                            return;
                        }

                        bar.classList.remove('bg-accent');
                        bar.classList.add('bg-rose-500');
                        status.textContent = 'Failed';
                        resolve({ status: 'failed', file: file.name, message: 'Upload failed' });
                    });

                    xhr.addEventListener('error', function () {
                        const bar = row.querySelector('.bar');
                        const status = row.querySelector('.status');
                        bar.classList.remove('bg-accent');
                        bar.classList.add('bg-rose-500');
                        status.textContent = 'Failed';
                        resolve({ status: 'failed', file: file.name, message: 'Network error' });
                    });

                    xhr.send(formData);
                });
            };

            form.addEventListener('submit', async function (event) {
                if (selectedFiles.length === 0) {
                    return;
                }

                event.preventDefault();

                const jobSelect = form.querySelector('select[name="job_id"]');
                const tokenInput = form.querySelector('input[name="_token"]');
                const asyncUrl = form.getAttribute('data-async-url') || '';
                const rankedUrl = form.getAttribute('data-ranked-url') || '';

                const jobId = (jobSelect?.value || '').trim();
                const token = tokenInput?.value || '';

                if (jobId === '' || token === '' || asyncUrl === '') {
                    form.submit();

                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Uploading...';

                const rows = Array.from(progressList.children);
                const summary = { processed: 0, duplicate: 0, failed: 0 };

                for (let i = 0; i < selectedFiles.length; i++) {
                    const file = selectedFiles[i];
                    const row = rows[i];
                    if (!row) {
                        continue;
                    }

                    const result = await uploadSingleFile(file, jobId, token, asyncUrl, row);
                    const state = result?.status || 'failed';
                    if (state === 'processed') {
                        summary.processed += 1;
                    } else if (state === 'duplicate') {
                        summary.duplicate += 1;
                    } else {
                        summary.failed += 1;
                    }
                }

                countLabel.textContent = 'Done. ' + summary.processed + ' processed, ' + summary.duplicate + ' duplicate skipped, ' + summary.failed + ' failed.';
                submitButton.disabled = false;
                submitButton.textContent = 'Upload and continue';

                if (rankedUrl !== '') {
                    window.location.href = rankedUrl + '?job_id=' + encodeURIComponent(jobId);
                }
            });
        })();
    </script>
@endsection