<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Education;
use App\Models\ApplicantLanguage;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Services\Ai\DuplicateCvDetectionService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicantProfileController extends Controller
{
    public function __construct(
        private readonly ResumeTextExtractor $resumeTextExtractor,
        private readonly DuplicateCvDetectionService $duplicateCvDetectionService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $applicant = $request->user()->applicant()
            ->with(['educations', 'experiences', 'certificates', 'skills', 'languages'])
            ->firstOrFail();

        return response()->json($applicant);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'national_id' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:80'],
        ]);

        $applicant = $request->user()->applicant;
        $applicant->update([
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'languages_json' => $validated['languages'] ?? [],
            'profile_completed_at' => now(),
        ]);

        if (array_key_exists('languages', $validated)) {
            $payload = collect($validated['languages'] ?? [])
                ->map(function (string $language): array {
                    return [
                        'language' => trim($language),
                        'proficiency' => null,
                    ];
                })
                ->filter(fn (array $row): bool => $row['language'] !== '')
                ->unique('language')
                ->values();

            ApplicantLanguage::query()->where('applicant_id', $applicant->id)->delete();
            if ($payload->isNotEmpty()) {
                $applicant->languages()->createMany($payload->all());
            }
        }

        return response()->json([
            'message' => 'Profile updated.',
            'applicant' => $applicant->fresh(),
        ]);
    }

    public function addEducation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:100'],
            'field_of_study' => ['nullable', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:30'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $education = Education::query()->create([
            ...$validated,
            'applicant_id' => $request->user()->applicant->id,
        ]);

        return response()->json(['education' => $education], 201);
    }

    public function addExperience(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $experience = WorkExperience::query()->create([
            ...$validated,
            'applicant_id' => $request->user()->applicant->id,
        ]);

        return response()->json(['experience' => $experience], 201);
    }

    public function addCertification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
        ]);

        $certificate = Certificate::query()->create([
            ...$validated,
            'applicant_id' => $request->user()->applicant->id,
        ]);

        return response()->json(['certificate' => $certificate], 201);
    }

    public function syncSkills(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'string', 'max:120'],
        ]);

        $applicant = $request->user()->applicant;
        $skillIds = collect($validated['skills'])
            ->map(function (string $skill): int {
                $normalized = strtolower(trim($skill));
                $model = Skill::query()->firstOrCreate([
                    'normalized_name' => $normalized,
                ], [
                    'name' => $skill,
                ]);

                return (int) $model->id;
            })
            ->values()
            ->all();

        $applicant->skills()->sync($skillIds);

        return response()->json([
            'message' => 'Skills updated.',
            'skills' => $applicant->skills()->get(['skills.id', 'skills.name']),
        ]);
    }

    public function uploadDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'cover_letter' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $applicant = $request->user()->applicant;

        if (isset($validated['cv'])) {
            if (!empty($applicant->cv_path) && Storage::disk('public')->exists($applicant->cv_path)) {
                Storage::disk('public')->delete($applicant->cv_path);
            }

            $path = $validated['cv']->store('applicants/cv', 'public');
            $hash = hash_file('sha256', (string) $validated['cv']->getRealPath()) ?: null;
            $cvText = $this->resumeTextExtractor->extract($validated['cv']);
            $fingerprint = $this->duplicateCvDetectionService->buildFingerprint($cvText);

            if ($hash !== null) {
                $duplicateScan = $this->duplicateCvDetectionService->detect($applicant, $hash, $cvText);
                if ($duplicateScan['is_duplicate']) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }

                    return response()->json([
                        'message' => 'CV rejected: ' . (string) ($duplicateScan['reason'] ?? 'Duplicate CV detected.'),
                        'duplicate' => $duplicateScan,
                    ], 409);
                }
            }

            $applicant->cv_path = $path;
            $applicant->cv_hash = $hash;
            $applicant->cv_text = $cvText;
            $applicant->cv_fingerprint = json_encode($fingerprint);
        }

        if (isset($validated['cover_letter'])) {
            if (!empty($applicant->cover_letter_path) && Storage::disk('public')->exists($applicant->cover_letter_path)) {
                Storage::disk('public')->delete($applicant->cover_letter_path);
            }

            $applicant->cover_letter_path = $validated['cover_letter']->store('applicants/cover-letter', 'public');
        }

        $applicant->save();

        return response()->json([
            'message' => 'Documents uploaded successfully.',
            'applicant' => $applicant->fresh(),
        ]);
    }
}
