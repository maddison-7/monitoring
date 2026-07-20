<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        @if ($document)
            <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Valid Document
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $document->typeLabel() }}</h1>
            <p class="mt-1 text-sm text-slate-500">This document was issued by the recruitment system and is authentic.</p>

            <dl class="mt-6 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Document ID</dt>
                    <dd class="font-mono font-semibold text-slate-900">{{ $document->document_id }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Applicant</dt>
                    <dd class="font-semibold text-slate-900">{{ $document->applicant_name }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Job Applied For</dt>
                    <dd class="font-semibold text-slate-900">{{ $document->job_title ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Date Generated</dt>
                    <dd class="font-semibold text-slate-900">{{ optional($document->generated_at)->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        @else
            <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-sm font-semibold text-red-700">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Invalid Document
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Document Not Found</h1>
            <p class="mt-2 text-sm text-slate-500">No document matches this ID. It may be invalid, tampered with, or expired.</p>
        @endif
    </div>
</body>
</html>
