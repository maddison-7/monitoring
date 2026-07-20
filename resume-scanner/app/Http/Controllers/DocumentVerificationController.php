<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use Illuminate\View\View;

class DocumentVerificationController extends Controller
{
    public function show(string $documentId): View
    {
        $document = GeneratedDocument::query()
            ->with(['application.job', 'interview'])
            ->where('document_id', strtoupper($documentId))
            ->first();

        return view('verify.document', [
            'pageTitle' => 'Document Verification',
            'document' => $document,
        ]);
    }
}
