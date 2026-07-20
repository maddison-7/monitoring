<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class DocumentVerificationService
{
    /**
     * Create a GeneratedDocument record and return it alongside a QR code
     * data URI that encodes the public verification URL.
     *
     * @return array{document: GeneratedDocument, qrDataUri: string, verifyUrl: string}
     */
    public function issue(
        string $type,
        int $applicantUserId,
        string $applicantName,
        ?string $jobTitle,
        ?int $applicationId = null,
        ?int $interviewId = null
    ): array {
        $document = GeneratedDocument::query()->create([
            'document_id' => strtoupper(Str::ulid()->toBase32()),
            'type' => $type,
            'application_id' => $applicationId,
            'interview_id' => $interviewId,
            'applicant_user_id' => $applicantUserId,
            'applicant_name' => $applicantName,
            'job_title' => $jobTitle,
            'generated_at' => now(),
        ]);

        $verifyUrl = route('documents.verify', ['documentId' => $document->document_id]);

        return [
            'document' => $document,
            'qrDataUri' => $this->qrDataUri($verifyUrl),
            'verifyUrl' => $verifyUrl,
        ];
    }

    public function qrDataUri(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($content);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
