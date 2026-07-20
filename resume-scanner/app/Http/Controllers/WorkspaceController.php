<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\JobPosting;
use App\Services\OpenAiScreeningService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly ResumeTextExtractor $resumeTextExtractor,
        private readonly OpenAiScreeningService $openAiScreeningService,
    ) {
    }

    public function uploadForm(Request $request): View
    {
        $maxUploadFiles = (int) config('services.upload.max_files', 30);
        $maxUploadFileSizeKb = (int) config('services.upload.max_file_size_kb', 5120);

        $jobs = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'title', 'department']);

        return view('UploadCv', [
            'jobs' => $jobs,
            'maxUploadFiles' => $maxUploadFiles,
            'maxUploadFileSizeMb' => round($maxUploadFileSizeKb / 1024, 1),
            'active' => 'jobs.index',
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        try {
            $maxUploadFiles = (int) config('services.upload.max_files', 30);
            $maxUploadFileSizeKb = (int) config('services.upload.max_file_size_kb', 5120);

            $validated = $request->validate([
                'job_id' => ['required', 'integer', 'exists:job_postings,id'],
                'resumes' => ['required', 'array', 'max:' . $maxUploadFiles],
                'resumes.*' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:' . $maxUploadFileSizeKb],
            ]);

            $jobPosting = JobPosting::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail((int) $validated['job_id']);

            $processedCount = 0;
            $duplicateSkipped = [];
            $failedUploads = [];

            foreach ($request->file('resumes') as $file) {
                $result = $this->processResumeFileForJob((int) $request->user()->id, $jobPosting, $file);

                if (($result['status'] ?? '') === 'processed') {
                    $processedCount++;
                    continue;
                }

                if (($result['status'] ?? '') === 'duplicate') {
                    $duplicateSkipped[] = (string) ($result['file'] ?? $file->getClientOriginalName());
                    continue;
                }

                $failedUploads[] = (string) ($result['file'] ?? $file->getClientOriginalName());
            }

            $statusParts = [];
            if ($processedCount > 0) {
                $statusParts[] = $processedCount . ' CV(s) uploaded, parsed, and scored successfully.';
            }

            if (count($duplicateSkipped) > 0) {
                $statusParts[] = count($duplicateSkipped) . ' duplicate file(s) skipped.';
            }

            if (count($failedUploads) > 0) {
                $statusParts[] = count($failedUploads) . ' file(s) failed processing.';
            }

            $flashType = ($processedCount > 0 || count($duplicateSkipped) > 0) ? 'success' : 'error';
            $flashMessage = $statusParts !== []
                ? implode(' ', $statusParts)
                : 'No CVs were processed. Please check the selected files and try again.';

            return redirect()
                ->route('ranked.candidates', ['job_id' => $jobPosting->id])
                ->with($flashType, $flashMessage);
        } catch (\Exception $exception) {
            return back()->with('error', 'Upload failed: ' . $exception->getMessage());
        }
    }

    public function uploadAsync(Request $request): JsonResponse
    {
        $maxUploadFileSizeKb = (int) config('services.upload.max_file_size_kb', 5120);

        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_postings,id'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:' . $maxUploadFileSizeKb],
        ]);

        $jobPosting = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail((int) $validated['job_id']);

        /** @var UploadedFile $resume */
        $resume = $validated['resume'];
        $result = $this->processResumeFileForJob((int) $request->user()->id, $jobPosting, $resume);

        return response()->json($result);
    }

    /**
     * @return array{status: string, file: string, message: string, candidate_id?: int}
     */
    private function processResumeFileForJob(int $userId, JobPosting $jobPosting, UploadedFile $file): array
    {
        $path = null;
        $originalName = (string) $file->getClientOriginalName();

        try {
            $fileHash = hash_file('sha256', (string) $file->getRealPath()) ?: null;

            /** @var JobPosting $jobPosting */
            $existingDuplicate = CandidateProfile::query()
                /** @var JobPosting $jobPosting */
                ->where('job_posting_id', $jobPosting->id)
                ->where('user_id', $userId)
                ->when($fileHash !== null, function ($query) use ($fileHash) {
                    $query->where('parsed_json->upload_file_hash', $fileHash);
                }, function ($query) use ($originalName) {
                    $query
                        ->where('resume_original_name', $originalName)
                        ->where('created_at', '>=', now()->subMinutes(15));
                })
                ->latest('id')
                ->first();

            if ($existingDuplicate instanceof CandidateProfile) {
                return [
                    'status' => 'duplicate',
                    'file' => $originalName,
                    'message' => 'Duplicate file skipped.',
                ];
            }

            $path = $file->store('resumes/' . date('Y/m/d'), 'public');

            $created = DB::transaction(function () use ($userId, $file, $path, $jobPosting, $fileHash) {
                $rawText = $this->resumeTextExtractor->extract($file);
                $anonymizedText = $this->openAiScreeningService->anonymizeText($rawText);

                $parsedProfile = $this->openAiScreeningService->parseResume($anonymizedText);
                $scoreResult = $this->openAiScreeningService->scoreCandidate($jobPosting, $parsedProfile, $anonymizedText);

                $parsedWithScreening = array_merge($parsedProfile, [
                    'upload_file_hash' => $fileHash,
                    'screening' => $scoreResult,
                ]);

                $scoreRecommendation = (string) ($scoreResult['recommendation'] ?? 'Review');
                /** @var CandidateProfile $created */
                return CandidateProfile::query()->create([
                    'job_posting_id' => $jobPosting->id,
                    'user_id' => $userId,
                    'resume_original_name' => $file->getClientOriginalName(),
                    'resume_path' => $path,
                    'raw_text' => $rawText,
                    'anonymized_text' => $anonymizedText,
                    'parsed_json' => $parsedWithScreening,
                    'skills_json' => $parsedProfile['skills'] ?? [],
                    'years_experience' => (float) ($parsedProfile['years_of_experience'] ?? 0),
                    'match_score' => (int) ($scoreResult['match_score'] ?? 0),
                    'recommendation' => $scoreRecommendation,
                    'status' => $scoreRecommendation,
                ]);
            });

            return [
                'status' => 'processed',
                'file' => $originalName,
                'message' => 'Uploaded and scored successfully.',
                'candidate_id' => (int) $created->id,
            ];
        } catch (Throwable $exception) {
            if (!empty($path) && Storage::disk('public')->exists((string) $path)) {
                Storage::disk('public')->delete((string) $path);
            }

            Log::warning('CV upload failed for one file.', [
                'user_id' => $userId,
                'job_id' => $jobPosting->id,
                'file' => $originalName,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'file' => $originalName,
                'message' => 'Failed to process this file.',
            ];
        }
    }

    public function jobDescription(Request $request): View
    {
        $jobs = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $editingJob = null;
        if ($jobs->isNotEmpty()) {
            $editJobId = (int) $request->query('edit_job_id', 0);
            if ($editJobId > 0) {
                $editingJob = $jobs->firstWhere('id', $editJobId);
            }
        }

        return view('JobDescription', [
            'jobs' => $jobs,
            'editingJob' => $editingJob,
            'active' => 'jobs.index',
        ]);
    }

    public function storeJobDescription(Request $request): RedirectResponse
    {
        try {
            $request->validate($this->jobDescriptionValidationRules());

            $skills = $this->parseSkillsFromInput((string) $request->input('skills'));

            $jobPosting = JobPosting::query()->create([
                'user_id' => $request->user()->id,
                'title' => $request->input('title'),
                'department' => $request->input('department'),
                'location' => $request->input('location'),
                'employment_type' => $request->input('employment_type'),
                'seniority' => $request->input('seniority'),
                'about' => $request->input('about'),
                'responsibilities' => $request->input('responsibilities'),
                'requirements' => $request->input('requirements'),
                'skills_text' => $request->input('skills'),
                'skills_json' => $skills,
            ]);

            return redirect()
                ->route('cv.upload.form')
                ->with('success', 'Job description saved. You can now upload CVs for "' . $jobPosting->title . '".');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to save job: ' . $exception->getMessage())->withInput();
        }
    }

    public function updateJobDescription(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        /** @var JobPosting $jobPosting */
        if ((int) $jobPosting->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to update this job description.');
        }

        try {
            $request->validate($this->jobDescriptionValidationRules());

            $skills = $this->parseSkillsFromInput((string) $request->input('skills'));

            $jobPosting->update([
                'title' => $request->input('title'),
                'department' => $request->input('department'),
                'location' => $request->input('location'),
                'employment_type' => $request->input('employment_type'),
                'seniority' => $request->input('seniority'),
                'about' => $request->input('about'),
                'responsibilities' => $request->input('responsibilities'),
                'requirements' => $request->input('requirements'),
                'skills_text' => $request->input('skills'),
                'skills_json' => $skills,
            ]);

            return redirect()
                ->route('job.description')
                ->with('success', 'Job description updated successfully.');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to update job: ' . $exception->getMessage())->withInput();
        }
    }

    public function deleteJobDescription(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        if ((int) $jobPosting->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to delete this job description.');
        }

        try {
            $jobTitle = (string) $jobPosting->title;
            $jobPosting->delete();

            return redirect()
                ->route('job.description')
                ->with('success', 'Job description "' . $jobTitle . '" deleted successfully.');
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to delete job: ' . $exception->getMessage());
        }
    }

    public function rankedCandidates(Request $request): View
    {
        $jobs = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'title', 'department']);

        $selectedJob = null;
        if ($jobs->isNotEmpty()) {
            $requestedJobId = (int) $request->query('job_id', 0);
            $selectedJob = $jobs->firstWhere('id', $requestedJobId) ?? $jobs->first();
        }

        $candidates = collect();
        if ($selectedJob) {
            $candidates = CandidateProfile::query()
                ->where('job_posting_id', $selectedJob->id)
                ->orderByDesc('match_score')
                ->orderByDesc('created_at')
                ->get()
                ->values()
                ->map(function ($candidate, int $index) {
                    $candidate->display_name = $this->resolveCandidateDisplayName($candidate, $index + 1);

                    return $candidate;
                });
        }

        return view('Rank-candidates', [
            'jobs' => $jobs,
            'selectedJob' => $selectedJob,
            'candidates' => $candidates,
            'active' => 'ranked-candidates',
        ]);
    }

    public function exportRankedCandidatesCsv(Request $request)
    {
        $jobId = (int) $request->query('job_id', 0);
        $job = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($jobId);
        $candidates = CandidateProfile::query()
            ->where('job_posting_id', $job->id)
            ->orderByDesc('match_score')
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ranked-candidates-job-' . $job->id . '.csv"',
        ];

        $columns = ['Rank', 'Candidate', 'Skills', 'Experience (years)', 'Score', 'Recommendation'];

        $callback = function () use ($candidates, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($candidates as $index => $candidate) {
                $skills = collect($candidate->skills_json ?? [])->take(4)->implode(', ');
                $candidateName = $this->resolveCandidateDisplayName($candidate, $index + 1);
                fputcsv($file, [
                    $index + 1,
                    $candidateName,
                    $skills,
                    number_format((float)($candidate->years_experience ?? 0), 1),
                    (int)($candidate->match_score ?? 0) . '%',
                    $candidate->recommendation ?? 'Review',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRankedCandidatesPdf(Request $request)
    {
        $jobId = (int) $request->query('job_id', 0);
        $job = JobPosting::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($jobId);
        $candidates = CandidateProfile::query()
            ->where('job_posting_id', $job->id)
            ->orderByDesc('match_score')
            ->orderByDesc('created_at')
            ->get();

        // Use dompdf if available, otherwise fallback to HTML
        if (class_exists('Barryvdh\\DomPDF\\Facade')) {
            $pdf = \Barryvdh\DomPDF\Facade::loadView('exports.ranked-candidates-pdf', [
                'job' => $job,
                'candidates' => $candidates,
            ]);
            return $pdf->download('ranked-candidates-job-' . $job->id . '.pdf');
        } else {
            // Fallback: render HTML table and set PDF headers (browser can print to PDF)
            $html = view('exports.ranked-candidates-pdf', [
                'job' => $job,
                'candidates' => $candidates,
            ])->render();
            return response($html, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="ranked-candidates-job-' . $job->id . '.pdf"',
            ]);
        }
    }

    public function showCandidateResult(Request $request, CandidateProfile $candidate): View
    {
        if ((int) $candidate->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to view this result.');
        }

        $candidate->loadMissing('jobPosting');

        /** @var CandidateProfile $candidate */
        $screening = (array) data_get($candidate->parsed_json, 'screening', []);
        $scoreBreakdown = (array) data_get($screening, 'score_breakdown', []);

        return view('Candidate-result', [
            'candidate' => $candidate,
            'job' => $candidate->jobPosting,
            'displayName' => $this->resolveCandidateDisplayName($candidate, 1),
            'screening' => $screening,
            'scoreBreakdown' => $scoreBreakdown,
            'cvUrl' => !empty($candidate->resume_path) ? Storage::url((string) $candidate->resume_path) : null,
            'active' => 'ranked-candidates',
        ]);
    }

    public function settings(Request $request): View
    {
        $role = (string) ($request->user()?->role ?? '');

        $layout = match ($role) {
            'applicant' => 'layouts.applicant',
            'admin' => 'layouts.admin',
            default => 'layouts.recruiter',
        };

        $active = match ($role) {
            'applicant' => 'settings',
            'admin' => 'dashboard',
            default => 'settings',
        };

        return view('settings', [
            'settingsLayout' => $layout,
            'active' => $active,
            'pageTitle' => 'Settings',
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        try {
            $password = (string) $request->input('password', '');
            $passwordConfirmation = (string) $request->input('password_confirmation', '');
            $shouldUpdatePassword = ($password !== '' && $passwordConfirmation !== '');

            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
                'locale' => ['nullable', 'string', Rule::in(array_keys(config('app.supported_locales')))],
                'profile_picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
                'profilePictureInput' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            ]);

            $validator->sometimes('password', ['required', 'string', 'min:8', 'same:password_confirmation'], function () use ($shouldUpdatePassword): bool {
                return $shouldUpdatePassword;
            });

            $validator->sometimes('password_confirmation', ['required', 'string', 'min:8'], function () use ($shouldUpdatePassword): bool {
                return $shouldUpdatePassword;
            });

            $validated = $validator->validate();

            $user = $request->user();
            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($shouldUpdatePassword && !empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $uploadField = collect(['profile_picture', 'profilePictureInput', 'avatar', 'photo'])
                ->first(fn (string $field): bool => $request->hasFile($field));

            if (is_string($uploadField)) {
                // Delete old profile picture if exists
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Store new profile picture
                $path = $request->file($uploadField)->store('profiles', 'public');
                $user->profile_picture = $path;
            }

            if (!empty($validated['locale'])) {
                $user->locale = $validated['locale'];
                session(['locale' => $validated['locale']]);
            }

            $user->save();

            return redirect()->route('settings')->with('success', 'Settings updated successfully!');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to update settings: ' . $exception->getMessage())->withInput();
        }
    }

    public function deleteCandidateResult(Request $request, CandidateProfile $candidate): RedirectResponse
    {
        if ((int) $candidate->user_id !== (int) $request->user()->id) {
            abort(403, 'You are not authorized to delete this result.');
        }

        $jobId = (int) $candidate->job_posting_id;

        try {
            if (!empty($candidate->resume_path) && Storage::disk('public')->exists((string) $candidate->resume_path)) {
                Storage::disk('public')->delete((string) $candidate->resume_path);
            }

            $candidate->delete();

            return redirect()
                ->route('ranked.candidates', ['job_id' => $jobId])
                ->with('success', 'Candidate result deleted successfully.');
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to delete candidate result: ' . $exception->getMessage());
        }
    }

    public function bulkDeleteCandidateResults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['required', 'integer'],
            'job_id' => ['nullable', 'integer'],
        ]);

        $candidateIds = collect($validated['candidate_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return back()->with('error', 'No candidate results were selected.');
        }

        $query = CandidateProfile::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $candidateIds->all());

        if (!empty($validated['job_id'])) {
            $query->where('job_posting_id', (int) $validated['job_id']);
        }

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            return back()->with('error', 'No authorized candidate results found to delete.');
        }

        try {
            foreach ($candidates as $candidate) {
                if (!empty($candidate->resume_path) && Storage::disk('public')->exists((string) $candidate->resume_path)) {
                    Storage::disk('public')->delete((string) $candidate->resume_path);
                }
            }

            CandidateProfile::query()
                ->whereIn('id', $candidates->pluck('id')->all())
                ->delete();

            $jobId = (int) ($validated['job_id'] ?? 0);

            return redirect()
                ->route('ranked.candidates', $jobId > 0 ? ['job_id' => $jobId] : [])
                ->with('success', $candidates->count() . ' candidate result(s) deleted successfully.');
        } catch (\Exception $exception) {
            return back()->with('error', 'Failed to delete selected candidate results: ' . $exception->getMessage());
        }
    }

    private function resolveCandidateDisplayName(mixed $candidate, int $fallbackIndex): string
    {
        $profileName = trim((string) data_get($candidate, 'parsed_json.full_name', ''));
        if ($this->isLikelyPersonName($profileName)) {
            return $profileName;
        }

        foreach (['raw_text', 'anonymized_text', 'parsed_json.summary'] as $sourcePath) {
            $resolved = $this->extractCandidateNameFromResumeText((string) data_get($candidate, $sourcePath, ''));
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $fileName = pathinfo((string) data_get($candidate, 'resume_original_name', ''), PATHINFO_FILENAME);
        if (trim($fileName) !== '') {
            return $fileName;
        }

        return 'Unknown Candidate ' . $fallbackIndex;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function jobDescriptionValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'in:full_time,part_time,contract,intern'],
            'seniority' => ['nullable', 'string', 'in:junior,mid,senior,lead'],
            'about' => ['required', 'string'],
            'responsibilities' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'skills' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseSkillsFromInput(string $skillsInput): array
    {
        return collect(preg_split('/[,\n]/', $skillsInput) ?: [])
            ->map(fn (string $skill): string => trim($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->values()
            ->all();
    }

    private function extractCandidateNameFromResumeText(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        $normalizedText = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

        $nameParts = $this->extractNameParts($normalizedText);
        if (!empty($nameParts['first']) && !empty($nameParts['surname'])) {
            $assembled = trim(implode(' ', array_filter([
                $nameParts['first'] ?? '',
                $nameParts['middle'] ?? '',
                $nameParts['surname'] ?? '',
            ], fn ($part): bool => trim((string) $part) !== '')));

            if ($this->isLikelyPersonName($assembled)) {
                return $assembled;
            }
        }

        // Handle CVs that explicitly declare name parts: surname/first name/middle name.
        if (preg_match('/surname\s+([\p{L}\'-]{2,30})(?:\s+middle\s+name\s+([\p{L}\'-]{2,30}))?(?:\s+first\s+name\s+([\p{L}\'-]{2,30}))/iu', $normalizedText, $match) === 1) {
            $surname = trim((string) ($match[1] ?? ''));
            $middle = trim((string) ($match[2] ?? ''));
            $first = trim((string) ($match[3] ?? ''));

            $assembled = trim(implode(' ', array_filter([$first, $middle, $surname], fn ($part): bool => $part !== '')));
            if ($this->isLikelyPersonName($assembled)) {
                return $assembled;
            }
        }

        if (preg_match('/(?:name|full\s+name)\s*[:\-]?\s*([\p{L}\'-]{2,30}(?:\s+[\p{L}\'-]{2,30}){1,3})/iu', $normalizedText, $nameMatch) === 1) {
            $candidate = trim((string) ($nameMatch[1] ?? ''));
            if ($this->isLikelyPersonName($candidate)) {
                return $candidate;
            }
        }

        // Common CV header line: Firstname Middlename Lastname on a dedicated early line.
        if (preg_match('/^\s*([\p{L}\'-]{2,30})\s+([\p{L}\'-]{2,30})(?:\s+([\p{L}\'-]{2,30}))?\s*$/mu', $text, $headerName) === 1) {
            $candidate = trim(implode(' ', array_filter([
                $headerName[1] ?? '',
                $headerName[2] ?? '',
                $headerName[3] ?? '',
            ], fn ($part): bool => trim((string) $part) !== '')));

            if ($this->isLikelyPersonName($candidate)) {
                return $candidate;
            }
        }

        $noiseTerms = [
            'resume', 'curriculum vitae', 'cover letter', 'profile', 'summary', 'objective',
            'contact', 'email', 'phone', 'address', 'skills', 'experience', 'education',
        ];

        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->take(12)
            ->values();

        foreach ($lines as $line) {
            $lowerLine = strtolower($line);
            foreach ($noiseTerms as $noiseTerm) {
                if (str_contains($lowerLine, $noiseTerm)) {
                    continue 2;
                }
            }

            if (str_contains($line, '@') || preg_match('/\d/', $line) === 1) {
                continue;
            }

            $normalized = preg_replace('/[^\p{L}\s\'-]/u', ' ', $line) ?? $line;
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
            $normalized = trim($normalized);

            if ($this->isLikelyPersonName($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @return array{first?: string, middle?: string, surname?: string}
     */
    private function extractNameParts(string $text): array
    {
        $parts = [];

        if (preg_match('/\bfirst\s+name\s*[:\-]?\s*([\p{L}\'-]{2,30})/iu', $text, $firstMatch) === 1) {
            $parts['first'] = trim((string) ($firstMatch[1] ?? ''));
        }

        if (preg_match('/\bmiddle\s+name\s*[:\-]?\s*([\p{L}\'-]{2,30})/iu', $text, $middleMatch) === 1) {
            $parts['middle'] = trim((string) ($middleMatch[1] ?? ''));
        }

        if (preg_match('/\bsurname\s*[:\-]?\s*([\p{L}\'-]{2,30})/iu', $text, $surnameMatch) === 1) {
            $parts['surname'] = trim((string) ($surnameMatch[1] ?? ''));
        }

        return $parts;
    }

    private function isLikelyPersonName(string $value): bool
    {
        $candidate = trim($value);
        if ($candidate === '') {
            return false;
        }

        $lower = strtolower($candidate);
        $blocked = [
            'candidate', 'my resume', 'resume', 'curriculum vitae', 'cover letter',
            'cv', 'unknown', 'profile', 'summary',
        ];

        foreach ($blocked as $token) {
            if (str_contains($lower, $token)) {
                return false;
            }
        }

        if (preg_match('/\d/', $candidate) === 1) {
            return false;
        }

        $parts = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $candidate) ?? $candidate)));
        if (count($parts) < 2 || count($parts) > 5) {
            return false;
        }

        $validParts = collect($parts)->every(function (string $part): bool {
            $piece = trim($part, "'\"");
            if ($piece === '' || mb_strlen($piece) < 2 || mb_strlen($piece) > 25) {
                return false;
            }

            return preg_match('/^[\p{L}\'-]+$/u', $piece) === 1;
        });

        return $validParts;
    }
}
