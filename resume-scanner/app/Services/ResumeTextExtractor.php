<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class ResumeTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf' => $this->extractFromPdf($file),
            'docx' => $this->extractFromDocx($file),
            'doc' => $this->extractFallback($file),
            default => throw new RuntimeException('Unsupported resume file type.'),
        };
    }

    private function extractFromPdf(UploadedFile $file): string
    {
        if (class_exists('Smalot\\PdfParser\\Parser')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());

            return $this->normalizeText($pdf->getText());
        }

        return $this->extractFallback($file);
    }

    private function extractFromDocx(UploadedFile $file): string
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Unable to read DOCX file.');
        }

        $xmlContent = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $text = strip_tags($xmlContent);

        return $this->normalizeText($text);
    }

    private function extractFallback(UploadedFile $file): string
    {
        $raw = @file_get_contents($file->getRealPath());
        if ($raw === false) {
            throw new RuntimeException('Failed to read resume file.');
        }

        // As a fallback, keep printable characters only.
        $text = preg_replace('/[^\PC\s]/u', ' ', $raw) ?? '';

        return $this->normalizeText($text);
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_substr($text, 0, 15000);
    }
}
