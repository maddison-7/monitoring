<div class="doc-header">
    <table class="doc-header-table">
        <tr>
            <td class="doc-header-left">
                <h1>{{ $title }}</h1>
                <p class="doc-id">Document ID: {{ $document->document_id }}</p>
                <p>Applicant: {{ $applicantName }}</p>
                <p>Job Applied For: {{ $jobTitle ?? 'N/A' }}</p>
                <p>Date Generated: {{ $document->generated_at->format('Y-m-d H:i') }}</p>
            </td>
            <td class="doc-header-right">
                <img src="{{ $qrDataUri }}" alt="Verification QR Code" class="doc-qr" />
                <p class="doc-verify-note">Scan to verify authenticity</p>
            </td>
        </tr>
    </table>
    <p class="doc-verify-info">
        This document can be verified at {{ $verifyUrl }} using Document ID <strong>{{ $document->document_id }}</strong>.
    </p>
    <hr class="doc-divider" />
</div>
