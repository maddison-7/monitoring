<?php

namespace App\Services\Ai;

use App\Models\Applicant;
use App\Models\Job;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RecruitmentScoringService
{
    private const RECOMMENDATION_TIERS = ['Highly Recommended', 'Recommended', 'Review Further', 'Not Recommended'];

    /** Maps Gemini's extracted_education_tier enum onto the same 0-4 scale used by CvAnalysisService::classifyEducationTier(). */
    private const EDUCATION_TIER_NAMES = ['none' => 0, 'diploma' => 1, 'bachelor' => 2, 'master' => 3, 'phd' => 4];

    public function __construct(private readonly CvAnalysisService $cvAnalysisService)
    {
    }

    /**
     * @return array{
     *     match_percentage: float,
     *     fit_score: float,
     *     recommendation_level: string,
     *     matched_skills: array<int, string>,
     *     missing_skills: array<int, string>,
     *     skills_score: float,
     *     experience_score: float,
     *     education_score: float,
     *     gpa_score: float|null,
     *     extracted_gpa: float|null,
     *     strengths: array<int, string>,
     *     weaknesses: array<int, string>,
     *     risk_factors: array<int, string>,
     *     hiring_advantages: array<int, string>,
     *     explanation: string,
     *     summary: string
     * }
     */
    public function score(Applicant $applicant, Job $job): array
    {
        $analysis = $this->cvAnalysisService->analyzeApplicant($applicant);

        $candidateSkills = collect((array) ($analysis['skills'] ?? []))
            ->map(fn (string $skill): string => $this->cvAnalysisService->normalizeSkill($skill))
            ->unique()
            ->values();

        $requiredSkills = collect((array) ($job->required_skills_json ?? []))
            ->map(fn (string $skill): string => $this->cvAnalysisService->normalizeSkill($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->unique()
            ->values();

        $matched = $requiredSkills
            ->filter(fn (string $required): bool => $candidateSkills->contains(
                fn (string $candidate): bool => $this->cvAnalysisService->isSkillMatch($required, $candidate)
            ))
            ->values()
            ->all();

        $missing = $requiredSkills
            ->reject(fn (string $skill): bool => in_array($skill, $matched, true))
            ->values()
            ->all();

        $skillsScore = $requiredSkills->count() > 0
            ? (($requiredSkills->count() - count($missing)) / $requiredSkills->count()) * 100
            : 50.0;

        $experienceYears = (float) ($analysis['experience_years'] ?? 0.0);
        $experienceTarget = max(1, (int) ($job->min_years_experience ?? 1));
        $experienceScore = min(100, ($experienceYears / $experienceTarget) * 100);

        $requiredEducationTier = $this->cvAnalysisService->classifyEducationTier((string) $job->qualifications);
        $candidateEducationTier = collect((array) ($analysis['education'] ?? []))
            ->map(fn (array $row): int => $this->cvAnalysisService->classifyEducationTier((string) ($row['level'] ?? '')))
            ->max() ?? 0;

        $educationScore = $this->computeEducationScore($requiredEducationTier, $candidateEducationTier);

        $candidateGpa = $analysis['gpa'] ?? null;
        $jobGpaRequirement = $this->extractGpaFromText((string) $job->qualifications . ' ' . (string) $job->description);
        $gpaScore = $this->scoreGpa($candidateGpa, $jobGpaRequirement);

        $baseScore = ($skillsScore * 0.5) + ($experienceScore * 0.25) + ($educationScore * 0.15) + (($gpaScore ?? 50.0) * 0.10);
        $baseScore = round(max(0, min(100, $baseScore)), 2);

        $fitScore = $this->computeFitScore(
            $candidateSkills,
            $matched,
            $missing,
            $experienceYears,
            $experienceTarget,
            $requiredEducationTier,
            $candidateEducationTier,
            $candidateGpa,
            $jobGpaRequirement,
            (array) ($analysis['certifications'] ?? []),
            (array) ($analysis['projects'] ?? []),
            (array) ($analysis['achievements'] ?? []),
            $baseScore
        );

        $apiKey = (string) config('services.gemini.api_key');

        if ($apiKey === '') {
            return $this->buildFallbackPayload($baseScore, $fitScore, $matched, $missing, $skillsScore, $experienceScore, $educationScore, $gpaScore);
        }

        $aiResult = $this->callGeminiEvaluation($job, $applicant, $analysis, $matched, $missing, $candidateGpa, $jobGpaRequirement, $baseScore);

        if ($aiResult === null) {
            return $this->buildFallbackPayload($baseScore, $fitScore, $matched, $missing, $skillsScore, $experienceScore, $educationScore, $gpaScore);
        }

        $aiScoreValue = (float) Arr::get($aiResult, 'overall_score', $baseScore);
        $aiScoreValue = max(0, min(100, $aiScoreValue));

        $finalScore = round(($baseScore * 0.55) + ($aiScoreValue * 0.45), 2);
        $finalScore = max(0, min(100, $finalScore));

        $recommendation = (string) Arr::get($aiResult, 'recommendation', '');
        if (!in_array($recommendation, self::RECOMMENDATION_TIERS, true)) {
            $recommendation = $this->recommendationFromScore($finalScore);
        }

        $explanation = trim((string) Arr::get($aiResult, 'explanation', ''));

        // --- Gap-filling enrichment from Gemini's direct reading of the CV file/attachments ---
        // Only fills genuine gaps (never overrides a stronger local signal), then feeds a
        // recomputed fit_score/education_score/gpa_score so ranking reflects anything only
        // visible in an attached certificate, transcript, or scanned page.
        $extractedGpa = is_numeric(Arr::get($aiResult, 'extracted_gpa')) ? (float) Arr::get($aiResult, 'extracted_gpa') : null;
        $enrichedGpa = $candidateGpa ?? $extractedGpa;
        $enrichedGpaScore = $enrichedGpa !== $candidateGpa ? $this->scoreGpa($enrichedGpa, $jobGpaRequirement) : $gpaScore;

        $extractedTier = self::EDUCATION_TIER_NAMES[(string) Arr::get($aiResult, 'extracted_education_tier', '')] ?? 0;
        $enrichedEducationTier = max($candidateEducationTier, $extractedTier);
        $enrichedEducationScore = $enrichedEducationTier > $candidateEducationTier
            ? $this->computeEducationScore($requiredEducationTier, $enrichedEducationTier)
            : $educationScore;

        $extractedSkills = collect((array) Arr::get($aiResult, 'extracted_skills', []))
            ->map(fn ($skill): string => $this->cvAnalysisService->normalizeSkill((string) $skill))
            ->filter(fn (string $skill): bool => $skill !== '');
        $enrichedCandidateSkills = $candidateSkills->concat($extractedSkills)->unique()->values();

        $enrichedMatched = $requiredSkills
            ->filter(fn (string $required): bool => $enrichedCandidateSkills->contains(
                fn (string $candidate): bool => $this->cvAnalysisService->isSkillMatch($required, $candidate)
            ))
            ->values()
            ->all();
        $enrichedMissing = $requiredSkills
            ->reject(fn (string $skill): bool => in_array($skill, $enrichedMatched, true))
            ->values()
            ->all();

        $enrichedFitScore = $this->computeFitScore(
            $enrichedCandidateSkills,
            $enrichedMatched,
            $enrichedMissing,
            $experienceYears,
            $experienceTarget,
            $requiredEducationTier,
            $enrichedEducationTier,
            $enrichedGpa,
            $jobGpaRequirement,
            (array) ($analysis['certifications'] ?? []),
            (array) ($analysis['projects'] ?? []),
            (array) ($analysis['achievements'] ?? []),
            $baseScore
        );

        return [
            'match_percentage' => $finalScore,
            'fit_score' => $enrichedFitScore,
            'recommendation_level' => $recommendation,
            'matched_skills' => $this->normalizeStringArray(Arr::get($aiResult, 'matched_skills', $enrichedMatched)),
            'missing_skills' => $this->normalizeStringArray(Arr::get($aiResult, 'missing_skills', $enrichedMissing)),
            'skills_score' => round($skillsScore, 2),
            'experience_score' => round($experienceScore, 2),
            'education_score' => round($enrichedEducationScore, 2),
            'gpa_score' => $enrichedGpaScore !== null ? round($enrichedGpaScore, 2) : null,
            'extracted_gpa' => $extractedGpa,
            'strengths' => $this->normalizeStringArray(Arr::get($aiResult, 'strengths', [])),
            'weaknesses' => $this->normalizeStringArray(Arr::get($aiResult, 'weaknesses', [])),
            'risk_factors' => $this->normalizeStringArray(Arr::get($aiResult, 'risk_factors', [])),
            'hiring_advantages' => $this->normalizeStringArray(Arr::get($aiResult, 'hiring_advantages', [])),
            'explanation' => $explanation,
            'summary' => $explanation !== '' ? $explanation : 'AI screening is decision-support only. Final decision remains with HR.',
        ];
    }

    private function computeEducationScore(int $requiredTier, int $candidateTier): float
    {
        return match (true) {
            $requiredTier === 0 => 50.0,
            $candidateTier >= $requiredTier => 100.0,
            default => match ($requiredTier - $candidateTier) {
                1 => 25.0,
                2 => 12.0,
                default => 0.0,
            },
        };
    }

    /**
     * Computes a deterministic, uncapped tie-break score so that candidates who all
     * clear the 0-100 match_percentage bar (e.g. every required skill present) can
     * still be ranked meaningfully — required-skill coverage gates the score first
     * (a candidate with fewer missing required skills always outranks one with more,
     * regardless of bonuses), then a richer weighted signal plus bonuses for
     * exceeding requirements breaks ties within the same missing-skill count.
     *
     * @param Collection<int, string> $candidateSkills
     * @param array<int, string> $matched
     * @param array<int, string> $missing
     * @param array<int, array<string, mixed>> $certifications
     * @param array<int, array<string, mixed>> $projects
     * @param array<int, array<string, mixed>> $achievements
     */
    private function computeFitScore(
        Collection $candidateSkills,
        array $matched,
        array $missing,
        float $experienceYears,
        int $experienceTarget,
        int $requiredEducationTier,
        int $candidateEducationTier,
        ?float $candidateGpa,
        ?float $jobGpaRequirement,
        array $certifications,
        array $projects,
        array $achievements,
        float $baseScore
    ): float {
        $avgMatchSimilarity = collect($matched)->isEmpty()
            ? 0.0
            : collect($matched)->avg(function (string $required) use ($candidateSkills): float {
                $best = 0.0;
                foreach ($candidateSkills as $candidate) {
                    $best = max($best, $this->cvAnalysisService->skillSimilarity($required, $candidate));
                }

                return $best;
            });

        $extraMatchedSkills = max(0, $candidateSkills->count() - count($matched));
        $skillBreadthBonus = min(15.0, $extraMatchedSkills * 1.5);

        $skillStrengthBonus = count($matched) > 0
            ? min(10.0, max(0.0, ($avgMatchSimilarity - 72.0) / 28.0 * 10.0))
            : 0.0;

        $experienceSurplusBonus = min(20.0, max(0.0, $experienceYears - $experienceTarget) * 4.0);

        $educationTierSurplusBonus = min(10.0, max(0, $candidateEducationTier - $requiredEducationTier) * 5.0);

        $gpaSurplusBonus = ($candidateGpa !== null && $jobGpaRequirement !== null && $jobGpaRequirement > 0.0)
            ? min(10.0, max(0.0, $candidateGpa - $jobGpaRequirement) * 20.0)
            : 0.0;

        $certificationsBonus = min(10.0, count($certifications) * 2.0);
        $projectsAchievementsBonus = min(10.0, (count($projects) + count($achievements)) * 1.0);

        $totalBonus = $skillBreadthBonus + $skillStrengthBonus + $experienceSurplusBonus
            + $educationTierSurplusBonus + $gpaSurplusBonus + $certificationsBonus + $projectsAchievementsBonus;

        return round((-1 * count($missing) * 100000) + ($baseScore * 100) + $totalBonus, 4);
    }

    /**
     * @param array<string, mixed> $analysis
     * @param array<int, string> $matched
     * @param array<int, string> $missing
     */
    private function callGeminiEvaluation(
        Job $job,
        Applicant $applicant,
        array $analysis,
        array $matched,
        array $missing,
        ?float $candidateGpa,
        ?float $jobGpaRequirement,
        float $baseScore
    ): ?array {
        $jobContext = [
            'title' => $job->title,
            'description' => $job->description,
            'qualifications' => $job->qualifications,
            'experience_level' => $job->experience_level,
            'min_years_experience' => $job->min_years_experience,
            'required_skills' => (array) ($job->required_skills_json ?? []),
            'gpa_requirement' => $jobGpaRequirement,
        ];

        $candidateContext = [
            'skills' => $analysis['skills'] ?? [],
            'matched_required_skills' => $matched,
            'missing_required_skills' => $missing,
            'years_experience' => $analysis['experience_years'] ?? 0,
            'education' => $analysis['education'] ?? [],
            'certifications' => $analysis['certifications'] ?? [],
            'projects' => $analysis['projects'] ?? [],
            'achievements' => $analysis['achievements'] ?? [],
            'gpa' => $candidateGpa,
            'cv_text_excerpt' => Str::limit((string) $applicant->cv_text, 8000),
        ];

        $cvFilePart = $this->buildCvFilePart($applicant);

        $prompt = <<<PROMPT
            You are a senior technical recruiter with 15+ years of experience running structured, evidence-based
            candidate screening. You are conducting a real hiring evaluation, not a demo — your judgment has
            consequences for both the candidate and the hiring manager, so be rigorous and honest.

            Think it through the way an experienced human recruiter would, step by step, before you answer:
            1. Required skills coverage — for every required skill, decide if the candidate's profile actually
               demonstrates it (via skills list, projects, achievements, or certifications), not just whether a
               keyword loosely appears.
            2. Experience depth and relevance — do the candidate's years of experience and history actually fit
               what this role needs, or is the experience in an unrelated area?
            3. Education and GPA fit — compare the candidate's education/GPA against what the job expects. If no
               GPA requirement is stated, do not penalize for it.
            4. Standout signals — certifications, projects, and achievements that make this candidate notably
               stronger or weaker than their raw skill match would suggest.
            5. Risk flags — anything a careful human recruiter would flag: experience gaps, mismatched seniority,
               overstated or vague claims, missing must-have skills.
            Weigh all of this together holistically to reach one overall judgment. Do not just average the
            category scores mechanically — a candidate missing a critical required skill should not score high
            even if other areas are strong, and a candidate with a minor gap but excellent relevant experience
            can still be a strong recommendation.

            Use the full 0-100 range meaningfully — do not default to 90-100 for every reasonable candidate.
            Reserve 90-100 for near-exact fits with no meaningful gaps, 70-89 for solid-but-imperfect fits,
            50-69 for partial fits with real gaps, and below 50 for weak fits, so that scores stay meaningfully
            differentiated across a large, varied applicant pool instead of clustering at the top.

            Ground every claim in the specific data given below. Every string you write — in matched_skills,
            missing_skills, strengths, weaknesses, risk_factors, hiring_advantages, and explanation — must
            reference an actual skill name, number (years, GPA, percentage), project, certification, or fact
            found in the job or candidate data. Never invent a fact that is not present in the data. Never use
            vague filler like "good communication skills" or "team player" unless it is explicitly evidenced by
            the candidate's data (a listed skill, a project description, an achievement). If the candidate data
            is thin, say so honestly instead of padding the answer with generic praise.

            Be critical and decisive: if this is a poor match, say so plainly in the explanation and name the
            specific missing skills or gaps that make it a poor match. If this is a strong match, cite the
            specific evidence that makes it strong. Avoid hedging language — give a clear, confident verdict a
            hiring manager could act on immediately.

            The candidate's actual CV file may be attached below as a document. If it is, read the ENTIRE
            document yourself — including any attached academic certificates, transcripts, or scanned/image
            pages within it — to verify or discover GPA, education level, skills, and experience. Our structured
            candidate profile below is only a lossy, automated approximation of the CV text and cannot see
            scanned images at all, so when the attached document shows something different (e.g. a GPA stated on
            a scanned transcript page, or a skill evidenced in a project screenshot), trust the document over the
            structured summary. Report anything you find this way in extracted_gpa, extracted_education_tier,
            and extracted_skills — leave them null/empty if you found nothing beyond what's already summarized.

            Job posting:
            {$this->jsonForPrompt($jobContext)}

            Candidate profile (skills, education, certifications, projects, achievements, GPA):
            {$this->jsonForPrompt($candidateContext)}

            Deterministic reference score computed from structured data (skills/experience/education/GPA
            weighting): {$baseScore}. Use this only as a sanity-check calibration point — your overall_score
            should reflect your own independent holistic judgment from the full profile above, and may
            legitimately differ from it when the data supports a different conclusion.
            PROMPT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'overall_score' => ['type' => 'INTEGER'],
                'recommendation' => [
                    'type' => 'STRING',
                    'enum' => self::RECOMMENDATION_TIERS,
                ],
                'matched_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'missing_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'strengths' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'weaknesses' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'risk_factors' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'hiring_advantages' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'explanation' => ['type' => 'STRING'],
                'extracted_gpa' => ['type' => 'NUMBER', 'nullable' => true],
                'extracted_education_tier' => [
                    'type' => 'STRING',
                    'enum' => array_keys(self::EDUCATION_TIER_NAMES),
                ],
                'extracted_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => [
                'overall_score', 'recommendation', 'matched_skills', 'missing_skills',
                'strengths', 'weaknesses', 'explanation',
            ],
        ];

        $extraParts = $cvFilePart !== null
            ? [['inline_data' => ['mime_type' => $cvFilePart['mime_type'], 'data' => $cvFilePart['data']]]]
            : [];

        return $this->callGeminiJson($prompt, (string) config('services.gemini.api_key'), $schema, $extraParts);
    }

    /**
     * Reads the applicant's CV file directly so it can be attached to the Gemini request as a
     * multimodal document part — only PDFs are attached (docx/doc are not supported
     * document-understanding mime types for Gemini), which is also exactly the common case for
     * CVs with a scanned academic certificate/transcript page glued in as extra PDF pages.
     *
     * @return array{mime_type: string, data: string}|null
     */
    private function buildCvFilePart(Applicant $applicant): ?array
    {
        $path = (string) ($applicant->cv_path ?? '');
        if ($path === '' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        try {
            $bytes = Storage::disk('public')->get($path);
        } catch (Throwable) {
            return null;
        }

        if ($bytes === null || $bytes === '' || strlen($bytes) > 15 * 1024 * 1024) {
            return null;
        }

        return ['mime_type' => 'application/pdf', 'data' => base64_encode($bytes)];
    }

    private function jsonForPrompt(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @param array<int, array<string, mixed>> $extraParts additional multimodal parts (e.g. an
     *  inline_data file) appended after the text prompt in the same request.
     */
    private function callGeminiJson(string $prompt, string $apiKey, ?array $schema = null, array $extraParts = []): ?array
    {
        // A single Gemini call (with "thinking" enabled on 2.5-flash) can take 40s+.
        // PHP's own max_execution_time (often 30s under Apache/mod_php) would otherwise
        // kill this request before the HTTP client's own timeout ever gets a chance to.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $generationConfig = [
            'temperature' => 0.35,
            // 2.5-flash spends part of this budget on internal "thinking" before writing the
            // actual JSON answer. With a CV file attached plus the full raw CV text, the model
            // has enough extra context that thinking alone can exhaust a small budget and leave
            // zero tokens for the real response (observed: finishReason MAX_TOKENS, empty parts).
            'maxOutputTokens' => 8192,
            'responseMimeType' => 'application/json',
        ];

        if ($schema !== null) {
            $generationConfig['responseSchema'] = $schema;
        }

        $response = $this->sendGeminiRequest(
            $baseUrl . '/models/' . rawurlencode($model) . ':generateContent',
            $apiKey,
            [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                            ...$extraParts,
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ],
            'Recruitment scoring'
        );

        if ($response === null) {
            return null;
        }

        $payload = $response->json();
        $parts = Arr::get($payload, 'candidates.0.content.parts', []);
        if (!is_array($parts)) {
            return null;
        }

        $text = collect($parts)
            ->map(fn ($part) => is_array($part) ? (string) Arr::get($part, 'text', '') : '')
            ->filter(fn (string $partText): bool => trim($partText) !== '')
            ->implode("\n");

        if (trim($text) === '') {
            return null;
        }

        return $this->parseJsonResponse($text);
    }

    private function parseJsonResponse(string $responseText): ?array
    {
        $trimmed = trim($responseText);

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            $decoded = json_decode(trim($matches[1]), true);

            return is_array($decoded) ? $decoded : null;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode(trim($matches[0]), true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * @param array<int, string> $matched
     * @param array<int, string> $missing
     * @return array<string, mixed>
     */
    private function buildFallbackPayload(
        float $baseScore,
        float $fitScore,
        array $matched,
        array $missing,
        float $skillsScore,
        float $experienceScore,
        float $educationScore,
        ?float $gpaScore
    ): array {
        $recommendation = $this->recommendationFromScore($baseScore);
        $missingText = $missing === [] ? 'no missing required skills' : ('missing: ' . implode(', ', $missing));

        return [
            'match_percentage' => $baseScore,
            'fit_score' => $fitScore,
            'recommendation_level' => $recommendation,
            'matched_skills' => $matched,
            'missing_skills' => $missing,
            'skills_score' => round($skillsScore, 2),
            'experience_score' => round($experienceScore, 2),
            'education_score' => round($educationScore, 2),
            'gpa_score' => $gpaScore !== null ? round($gpaScore, 2) : null,
            'extracted_gpa' => null,
            'strengths' => $matched,
            'weaknesses' => $missing,
            'risk_factors' => [],
            'hiring_advantages' => [],
            'explanation' => "Deterministic screening score of {$baseScore}% based on skills, experience, education, and GPA signals ({$missingText}).",
            'summary' => 'AI screening is decision-support only. Final decision remains with HR.',
        ];
    }

    private function recommendationFromScore(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Highly Recommended',
            $score >= 65 => 'Recommended',
            $score >= 40 => 'Review Further',
            default => 'Not Recommended',
        };
    }

    private function scoreGpa(?float $candidateGpa, ?float $jobGpaRequirement): ?float
    {
        if ($candidateGpa === null) {
            return null;
        }

        $candidateGpa = max(0.0, min(4.0, $candidateGpa));

        if ($jobGpaRequirement === null || $jobGpaRequirement <= 0.0) {
            return round(($candidateGpa / 4.0) * 100, 2);
        }

        if ($candidateGpa >= $jobGpaRequirement) {
            return 100.0;
        }

        return round(max(0, min(100, ($candidateGpa / $jobGpaRequirement) * 100)), 2);
    }

    private function extractGpaFromText(string $text): ?float
    {
        if (trim($text) === '') {
            return null;
        }

        if (preg_match('/(?:gpa|cgpa|grade point average)\s*(?:of|:|is|=)?\s*(\d(?:\.\d{1,2})?)(?:\s*\/\s*(\d(?:\.\d{1,2})?))?/i', $text, $matches) !== 1) {
            return null;
        }

        $value = (float) $matches[1];
        $denominator = isset($matches[2]) ? (float) $matches[2] : 4.0;

        if ($denominator <= 0.0) {
            return null;
        }

        $normalized = ($value / $denominator) * 4.0;

        return round(max(0.0, min(4.0, $normalized)), 2);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    /**
     * Sends the Gemini request, automatically retrying once if the API responds with a
     * transient 429 (rate limit) status — Gemini's free tier caps requests per minute, and
     * a short burst of chat/scoring activity can trip it. Honors the API's suggested
     * retry-after delay, capped to keep the overall request time reasonable.
     *
     * @param array<string, mixed> $body
     */
    private function sendGeminiRequest(string $url, string $apiKey, array $body, string $logLabel, int $maxAttempts = 2): ?\Illuminate\Http\Client\Response
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(60)
                    ->withOptions(['verify' => storage_path('certs/cacert.pem')])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->withQueryParameters(['key' => $apiKey])
                    ->post($url, $body);
            } catch (Throwable $exception) {
                Log::warning("{$logLabel} Gemini request failed before response.", [
                    'attempt' => $attempt,
                    'exception' => $exception->getMessage(),
                ]);

                return null;
            }

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 429 && $attempt < $maxAttempts) {
                $delaySeconds = $this->extractRetryDelaySeconds((string) $response->body());
                Log::warning("{$logLabel} Gemini request rate-limited, retrying shortly.", [
                    'attempt' => $attempt,
                    'retry_after_seconds' => $delaySeconds,
                ]);
                sleep($delaySeconds);
                continue;
            }

            Log::warning("{$logLabel} Gemini request returned non-success status.", [
                'attempt' => $attempt,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 600),
            ]);

            return null;
        }

        return null;
    }

    private function extractRetryDelaySeconds(string $responseBody): int
    {
        if (preg_match('/"retryDelay"\s*:\s*"(\d+(?:\.\d+)?)s"/', $responseBody, $matches) === 1) {
            return max(1, min(8, (int) ceil((float) $matches[1])));
        }

        return 4;
    }
}
