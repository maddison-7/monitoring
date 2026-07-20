<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\JobPosting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiScreeningService
{
    private const DEFAULT_MODEL = 'gemini-2.5-flash';

    private const DEFAULT_WEIGHTS = [
        'scoring_skills_weight' => 50,
        'scoring_experience_weight' => 30,
        'scoring_education_weight' => 20,
    ];

    private const STOP_WORDS = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'have', 'in', 'is',
        'it', 'of', 'on', 'or', 'our', 'the', 'to', 'with', 'using', 'use', 'used', 'working',
    ];

    private const SKILL_ALIASES = [
        'js' => 'javascript',
        'nodejs' => 'node.js',
        'node js' => 'node.js',
        'reactjs' => 'react',
        'react js' => 'react',
        'ts' => 'typescript',
        'py' => 'python',
        'c sharp' => 'c#',
        'csharp' => 'c#',
        'dotnet' => '.net',
        'postgres' => 'postgresql',
        'postgres sql' => 'postgresql',
        'mysql database' => 'mysql',
        'rest api' => 'api',
        'human resources' => 'hr',
        'talent acquisition' => 'recruitment',
        'social media marketing' => 'social media',
        'search engine optimization' => 'seo',
        'search engine marketing' => 'sem',
        'google ads' => 'ppc',
        'supply chain management' => 'supply chain',
        'learning management system' => 'lms',
        'customer relationship management' => 'crm',
    ];

    private const KNOWN_SKILLS = [
        'php', 'laravel', 'symfony', 'mysql', 'postgresql', 'sqlite', 'redis', 'mongodb',
        'javascript', 'typescript', 'react', 'vue', 'angular', 'node.js', 'express',
        'python', 'django', 'flask', 'fastapi', 'java', 'spring', 'c#', '.net',
        'html', 'css', 'tailwind', 'bootstrap', 'docker', 'kubernetes',
        'aws', 'azure', 'gcp', 'git', 'linux', 'api', 'rest', 'graphql',
        'excel', 'microsoft excel', 'microsoft word', 'microsoft office', 'ms office',
        'powerpoint', 'figma', 'power bi', 'tableau',
        'communication', 'teamwork', 'leadership', 'problem solving', 'time management',
        'customer service', 'data entry', 'report writing',
        'recruitment', 'hr', 'onboarding', 'payroll processing', 'employee relations',
        'performance management', 'benefits administration', 'labor law',
        'seo', 'sem', 'content marketing', 'social media', 'email marketing', 'ppc',
        'google analytics', 'brand management', 'copywriting', 'market research',
        'contract drafting', 'legal research', 'litigation support', 'compliance',
        'case management', 'intellectual property', 'corporate law',
        'inventory management', 'supply chain', 'warehouse operations', 'procurement',
        'fleet management', 'shipping', 'logistics coordination',
        'lesson planning', 'curriculum development', 'classroom management',
        'student assessment', 'lms', 'training delivery',
        'front desk', 'reservation systems', 'housekeeping', 'food safety',
        'event coordination', 'guest relations',
        'bookkeeping', 'financial reporting', 'tax preparation', 'auditing',
        'accounts payable', 'accounts receivable',
    ];

    private const EDUCATION_SIGNALS = [
        'associate', 'bachelor', 'bachelors', 'master', 'masters', 'mba', 'phd', 'doctorate',
        'degree', 'diploma', 'certification', 'certificate', 'computer science', 'engineering',
        'information technology', 'information systems', 'software engineering', 'data science',
    ];

    private const SKILL_TAXONOMY = [
        'language' => ['php', 'javascript', 'typescript', 'python', 'java', 'c#'],
        'framework' => ['laravel', 'symfony', 'react', 'vue', 'angular', 'django', 'flask', 'fastapi', 'spring', '.net', 'express'],
        'database' => ['mysql', 'postgresql', 'sqlite', 'mongodb', 'redis'],
        'cloud_devops' => ['aws', 'azure', 'gcp', 'docker', 'kubernetes', 'linux', 'git'],
        'integration' => ['api', 'rest', 'graphql'],
        'analytics_tools' => ['excel', 'microsoft excel', 'power bi', 'tableau', 'figma'],
        'soft_skills' => ['communication', 'teamwork', 'leadership', 'problem solving', 'time management', 'customer service'],
        'operations' => ['data entry', 'report writing', 'microsoft office', 'microsoft word', 'ms office', 'powerpoint'],
        'hr' => ['hr', 'recruitment', 'onboarding', 'employee relations', 'performance management', 'benefits administration'],
        'marketing' => ['seo', 'sem', 'content marketing', 'social media', 'email marketing', 'ppc', 'google analytics', 'brand management', 'copywriting', 'market research'],
        'legal' => ['contract drafting', 'legal research', 'litigation support', 'compliance', 'case management', 'intellectual property', 'corporate law'],
        'logistics' => ['inventory management', 'supply chain', 'warehouse operations', 'procurement', 'fleet management', 'shipping', 'logistics coordination'],
        'education' => ['lesson planning', 'curriculum development', 'classroom management', 'student assessment', 'lms', 'training delivery'],
        'hospitality' => ['front desk', 'reservation systems', 'housekeeping', 'food safety', 'event coordination', 'guest relations'],
        'finance_ops' => ['bookkeeping', 'financial reporting', 'tax preparation', 'auditing', 'accounts payable', 'accounts receivable', 'payroll processing'],
    ];

    private const CATEGORY_WEIGHTS = [
        'language' => 25,
        'framework' => 25,
        'database' => 15,
        'cloud_devops' => 15,
        'integration' => 10,
        'analytics_tools' => 5,
        'soft_skills' => 3,
        'operations' => 2,
        'hr' => 0,
        'marketing' => 0,
        'legal' => 0,
        'logistics' => 0,
        'education' => 0,
        'hospitality' => 0,
        'finance_ops' => 0,
    ];

    private const DOMAIN_CATEGORY_WEIGHTS = [
        'it' => [
            'language' => 25,
            'framework' => 25,
            'database' => 15,
            'cloud_devops' => 15,
            'integration' => 10,
            'analytics_tools' => 5,
            'soft_skills' => 3,
            'operations' => 2,
        ],
        'sales' => [
            'operations' => 25,
            'soft_skills' => 20,
            'analytics_tools' => 20,
            'marketing' => 20,
            'finance_ops' => 10,
            'integration' => 5,
        ],
        'customer_support' => [
            'operations' => 30,
            'soft_skills' => 30,
            'analytics_tools' => 15,
            'hr' => 10,
            'hospitality' => 10,
            'integration' => 5,
        ],
        'finance' => [
            'finance_ops' => 35,
            'operations' => 20,
            'analytics_tools' => 20,
            'legal' => 10,
            'soft_skills' => 10,
            'integration' => 5,
        ],
        'healthcare' => [
            'operations' => 30,
            'soft_skills' => 20,
            'hospitality' => 15,
            'education' => 15,
            'legal' => 15,
            'analytics_tools' => 5,
        ],
        'hr' => [
            'hr' => 35,
            'operations' => 20,
            'soft_skills' => 20,
            'legal' => 10,
            'analytics_tools' => 10,
            'education' => 5,
        ],
        'marketing' => [
            'marketing' => 40,
            'analytics_tools' => 20,
            'soft_skills' => 15,
            'operations' => 10,
            'integration' => 10,
            'education' => 5,
        ],
        'legal' => [
            'legal' => 45,
            'soft_skills' => 15,
            'operations' => 15,
            'finance_ops' => 10,
            'education' => 10,
            'analytics_tools' => 5,
        ],
        'logistics' => [
            'logistics' => 40,
            'operations' => 25,
            'soft_skills' => 10,
            'analytics_tools' => 10,
            'integration' => 10,
            'finance_ops' => 5,
        ],
        'education' => [
            'education' => 40,
            'soft_skills' => 20,
            'operations' => 20,
            'analytics_tools' => 10,
            'hr' => 5,
            'integration' => 5,
        ],
        'hospitality' => [
            'hospitality' => 40,
            'soft_skills' => 25,
            'operations' => 20,
            'analytics_tools' => 5,
            'logistics' => 5,
            'education' => 5,
        ],
    ];

    private const DOMAIN_SCORE_WEIGHTS = [
        'it' => [
            'scoring_skills_weight' => 55,
            'scoring_experience_weight' => 30,
            'scoring_education_weight' => 15,
        ],
        'sales' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 40,
            'scoring_education_weight' => 15,
        ],
        'customer_support' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 40,
            'scoring_education_weight' => 15,
        ],
        'finance' => [
            'scoring_skills_weight' => 50,
            'scoring_experience_weight' => 35,
            'scoring_education_weight' => 15,
        ],
        'healthcare' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 35,
            'scoring_education_weight' => 20,
        ],
        'hr' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 35,
            'scoring_education_weight' => 20,
        ],
        'marketing' => [
            'scoring_skills_weight' => 50,
            'scoring_experience_weight' => 35,
            'scoring_education_weight' => 15,
        ],
        'legal' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 30,
            'scoring_education_weight' => 25,
        ],
        'logistics' => [
            'scoring_skills_weight' => 50,
            'scoring_experience_weight' => 35,
            'scoring_education_weight' => 15,
        ],
        'education' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 30,
            'scoring_education_weight' => 25,
        ],
        'hospitality' => [
            'scoring_skills_weight' => 45,
            'scoring_experience_weight' => 40,
            'scoring_education_weight' => 15,
        ],
    ];

    private const DOMAIN_KEYWORDS = [
        'it' => ['software', 'developer', 'engineer', 'backend', 'frontend', 'full stack', 'qa', 'devops', 'cloud', 'api', 'database', 'programming'],
        'sales' => ['sales', 'lead generation', 'prospecting', 'crm', 'pipeline', 'quota', 'account executive'],
        'customer_support' => ['customer service', 'call center', 'ticketing', 'support', 'help desk'],
        'finance' => ['accounting', 'bookkeeping', 'payroll', 'audit', 'tax', 'financial reporting'],
        'healthcare' => ['nurse', 'clinical', 'patient care', 'hospital', 'medical'],
        'hr' => ['human resources', 'hr', 'recruitment', 'talent acquisition', 'onboarding', 'employee relations'],
        'marketing' => ['marketing', 'seo', 'sem', 'ppc', 'social media', 'content', 'campaign', 'brand'],
        'legal' => ['legal', 'law', 'attorney', 'paralegal', 'contract', 'litigation', 'compliance'],
        'logistics' => ['logistics', 'warehouse', 'inventory', 'supply chain', 'shipping', 'procurement', 'dispatch'],
        'education' => ['teacher', 'teaching', 'curriculum', 'classroom', 'education', 'instruction', 'training'],
        'hospitality' => ['hotel', 'hospitality', 'front desk', 'reservation', 'housekeeping', 'guest service', 'restaurant'],
    ];

    private const HARD_CONSTRAINT_PATTERNS = [
        'must have',
        'must-have',
        'mandatory',
        'required',
        'essential',
    ];

    public function parseResume(string $resumeText): array
    {
        $processedText = $this->preprocessText($resumeText);
        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            return $this->heuristicParse($processedText['normalized']);
        }

        $prompt = <<<PROMPT
            You are an expert resume parser. Read the resume text below the way an experienced recruiter would
            and extract a factual candidate profile. Only extract what is actually stated or clearly implied in
            the text — never invent a name, skill, or number that is not supported by the text.

            For skills: include both explicitly listed skills and skills clearly demonstrated through described
            work (e.g. a bullet point describing building a REST API implies "api"), using concise, canonical
            skill names (e.g. "javascript" not "JS programming language").

            For years_of_experience: infer the candidate's total relevant professional experience from dates,
            job history, or explicit statements ("5 years of experience"). If genuinely not determinable, use 0.

            For gpa: extract the candidate's GPA as a plain number on a 4.0 scale if stated anywhere (e.g. "GPA:
            3.8/4.0", "CGPA 3.5", "3.7 GPA"), converting to a 4.0 scale if a different scale is given. If no GPA
            is mentioned, return null.

            Resume text:
            {$processedText['normalized']}
            PROMPT;

        $parseSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'full_name' => ['type' => 'STRING'],
                'email' => ['type' => 'STRING', 'nullable' => true],
                'phone' => ['type' => 'STRING', 'nullable' => true],
                'skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'years_of_experience' => ['type' => 'NUMBER'],
                'gpa' => ['type' => 'NUMBER', 'nullable' => true],
                'summary' => ['type' => 'STRING'],
            ],
            'required' => ['full_name', 'skills', 'years_of_experience', 'summary'],
        ];

        $result = $this->callGeminiJson($prompt, $apiKey, $parseSchema);

        if (!is_array($result)) {
            return $this->heuristicParse($processedText['normalized']);
        }

        return $this->normalizeParsedProfile($result, $processedText['normalized']);
    }

    public function scoreCandidate(JobPosting $jobPosting, array $parsedCandidate, string $anonymizedText): array
    {
        $jobSkills = $this->extractSkills($jobPosting);
        $candidateProfile = $this->preprocessText($this->buildCandidateText($parsedCandidate, $anonymizedText));
        $candidateSkills = $this->extractCandidateSkills($parsedCandidate, $candidateProfile);

        $mustHaveSkills = $this->inferMustHaveSkills($jobPosting, $jobSkills);
        $skillMatchResult = $this->matchSkills($jobSkills, $candidateSkills);
        $skillScore = $skillMatchResult['skill_score'];
        $matched = $skillMatchResult['matched'];
        $missing = $skillMatchResult['missing'];
        $categoryBreakdown = $this->buildCategoryBreakdown($jobSkills, $candidateSkills);

        $hardConstraint = $this->evaluateHardConstraints($mustHaveSkills, $candidateSkills);

        $years = (float) Arr::get($parsedCandidate, 'years_of_experience', 0);
        $experienceScore = $this->scoreExperience($years, (string) ($jobPosting->seniority ?? ''));
        $jobEducationContext = $this->preprocessText((string) $jobPosting->requirements . ' ' . (string) $jobPosting->about);
        $jobGpaRequirement = $this->extractGpaFromText($jobEducationContext['normalized']);
        $parsedGpa = Arr::get($parsedCandidate, 'gpa');
        $candidateGpa = is_numeric($parsedGpa)
            ? max(0.0, min(4.0, (float) $parsedGpa))
            : $this->extractGpaFromText($candidateProfile['normalized']);
        $educationScore = $this->scoreEducation($jobEducationContext, $candidateProfile, $jobGpaRequirement, $candidateGpa);
        $gpaScore = $jobGpaRequirement !== null
            ? $this->scoreGpaMatch($jobGpaRequirement, $candidateGpa)
            : null;

        $weights = $this->resolveWeights();
        $localScore = $this->weightedScore($skillScore, $experienceScore, $educationScore, $weights);
        $localScore = max(0, min(100, $localScore));

        if (!$hardConstraint['passed']) {
            $localScore = min($localScore, 35);
        }

        $apiKey = (string) config('services.gemini.api_key');
        $baseBreakdown = [
            'skill_score' => $skillScore,
            'experience_score' => $experienceScore,
            'education_score' => $educationScore,
            'gpa_score' => $gpaScore,
            'gpa_requirement' => $jobGpaRequirement,
            'candidate_gpa' => $candidateGpa,
            'hard_constraint' => $hardConstraint,
            'category_breakdown' => $categoryBreakdown,
            'weights' => $weights,
        ];

        if ($apiKey === '') {
            return $this->buildScorePayload($localScore, $matched, $missing, $baseBreakdown);
        }

        $prompt = <<<PROMPT
            You are an expert ATS recruiter and job evaluator running a real, consequential hiring assessment —
            be rigorous and honest, not diplomatic filler.

            Think like a human recruiter, step by step, before producing your answer:
            1. Compare the candidate's actual demonstrated skills (not just keyword overlap) against the job's
               required and must-have skills. A skill only counts as matched if the candidate profile or resume
               text actually shows evidence of it.
            2. Judge experience relevance and depth against the job's seniority level, not just years as a
               number — 5 years in an unrelated field is not the same as 5 years directly relevant.
            3. Weigh education and GPA against what the job expects, if anything is stated.
            4. Decide whether every must-have skill is genuinely covered. If any must-have skill is missing,
               this is a hard blocker regardless of how good the rest of the profile looks.
            5. Form one clear, decisive overall judgment — do not hedge. If the fit is weak, say exactly why
               (name the missing skills/gaps). If the fit is strong, cite exactly what makes it strong.

            Ground every word in the actual job and candidate data below. Never invent a skill, number, or fact
            that isn't present in the data. Do not add fields or commentary outside the JSON response.

            Job title: {$jobPosting->title}
            Seniority: {$jobPosting->seniority}
            Job requirements: {$jobPosting->requirements}
            Job skills: {$this->jsonForPrompt($jobSkills)}
            Must-have skills: {$this->jsonForPrompt($mustHaveSkills)}
            Candidate parsed profile: {$this->jsonForPrompt($parsedCandidate)}
            Resume text (anonymized): {$anonymizedText}
            Deterministic reference score (calibration only): {$localScore}
            PROMPT;

        $scoreSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'match_score' => ['type' => 'INTEGER'],
                'matched_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'missing_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'overall_assessment' => ['type' => 'STRING'],
                'recommendation' => ['type' => 'STRING', 'enum' => ['Shortlisted', 'Review', 'Rejected']],
                'hard_constraints_passed' => ['type' => 'BOOLEAN'],
                'missing_must_have_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => [
                'match_score', 'matched_skills', 'missing_skills', 'overall_assessment',
                'recommendation', 'hard_constraints_passed', 'missing_must_have_skills',
            ],
        ];

        $aiResult = $this->callGeminiJson($prompt, $apiKey, $scoreSchema);

        if (!is_array($aiResult)) {
            return $this->buildScorePayload($localScore, $matched, $missing, $baseBreakdown);
        }

        $aiScore = (int) Arr::get($aiResult, 'match_score', $localScore);
        $aiScore = max(0, min(100, $aiScore));

        $aiHardPassed = (bool) Arr::get($aiResult, 'hard_constraints_passed', $hardConstraint['passed']);
        $aiMissingMust = $this->normalizeSkillOutput((array) Arr::get($aiResult, 'missing_must_have_skills', $hardConstraint['missing_must_have']));

        $finalScore = (int) round(($localScore * 0.55) + ($aiScore * 0.45));
        $finalScore = max(0, min(100, $finalScore));

        if (!$aiHardPassed || $aiMissingMust !== []) {
            $finalScore = min($finalScore, 35);
        }

        $assessment = (string) Arr::get($aiResult, 'overall_assessment', $this->recommendationFromScore($finalScore));
        $matchedSkills = $this->normalizeSkillOutput(Arr::get($aiResult, 'matched_skills', $matched));
        $missingSkills = $this->normalizeSkillOutput(Arr::get($aiResult, 'missing_skills', $missing));
        $recommendation = (string) Arr::get($aiResult, 'recommendation', $this->recommendationFromScore($finalScore));

        if (!in_array($recommendation, ['Shortlisted', 'Review', 'Rejected'], true)) {
            $recommendation = $this->recommendationFromScore($finalScore);
        }

        return [
            'match_score' => $finalScore,
            'matched_skills' => $matchedSkills,
            'missing_skills' => $missingSkills,
            'overall_assessment' => $assessment,
            'recommendation' => $recommendation,
            'score_breakdown' => [
                'skill_score' => $skillScore,
                'experience_score' => $experienceScore,
                'education_score' => $educationScore,
                'hard_constraint' => [
                    'must_have' => $hardConstraint['must_have'],
                    'missing_must_have' => $aiMissingMust,
                    'passed' => $aiHardPassed,
                ],
                'gpa_score' => $gpaScore,
                'gpa_requirement' => $jobGpaRequirement,
                'candidate_gpa' => $candidateGpa,
                'category_breakdown' => $categoryBreakdown,
                'local_score_before_ai' => $localScore,
                'ai_reference_score' => $aiScore,
                'weights' => $weights,
            ],
        ];
    }

    /**
     * @param Collection<int, mixed> $candidates
     * @return Collection<int, mixed>
     */
    public function rankCandidates(JobPosting $jobPosting, Collection $candidates): Collection
    {
        $fallback = $candidates
            ->sortBy([
                ['match_score', 'desc'],
                ['id', 'asc'],
            ])
            ->values();

        if ($fallback->count() <= 1) {
            return $fallback;
        }

        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            return $fallback;
        }

        $candidatePayload = $fallback->map(function ($candidate): array {
            return [
                'id' => (int) ($candidate->id ?? 0),
                'skills' => array_values(array_map('strval', (array) ($candidate->skills_json ?? []))),
                'years_experience' => (float) ($candidate->years_experience ?? 0),
                'match_score' => (int) ($candidate->match_score ?? 0),
                'recommendation' => (string) ($candidate->recommendation ?? 'Review'),
                'parsed_profile' => (array) ($candidate->parsed_json ?? []),
            ];
        })->values()->all();

        $prompt = <<<PROMPT
            You are a senior recruiter ranking shortlisted candidates for a single role, best fit first. Do not
            just re-sort by match_score — actually compare each candidate's skills, experience relevance, and
            profile against the job below, the way a human recruiter reviewing a shortlist would, and break ties
            or reorder where the numeric score alone doesn't capture the real difference in fit (e.g. one
            candidate covers a critical skill the other lacks, or has more directly relevant experience).

            Job:
            {$this->jsonForPrompt([
                'title' => $jobPosting->title,
                'department' => $jobPosting->department,
                'seniority' => $jobPosting->seniority,
                'requirements' => $jobPosting->requirements,
                'skills' => $jobPosting->skills_json,
            ])}

            Candidates:
            {$this->jsonForPrompt($candidatePayload)}

            Include every candidate id exactly once in ordered_candidate_ids.
            PROMPT;

        $rankSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'ordered_candidate_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                'ranking_reasoning' => ['type' => 'STRING'],
            ],
            'required' => ['ordered_candidate_ids'],
        ];

        $ranking = $this->callGeminiJson($prompt, $apiKey, $rankSchema);
        $orderedIds = collect((array) Arr::get($ranking, 'ordered_candidate_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($orderedIds->count() === 0) {
            return $fallback;
        }

        $indexedCandidates = $fallback->keyBy('id');
        $apiOrdered = $orderedIds
            ->map(fn (int $id) => $indexedCandidates->get($id))
            ->filter();

        if ($apiOrdered->count() === 0) {
            return $fallback;
        }

        $missingFromApi = $fallback->reject(fn ($candidate) => $orderedIds->contains((int) ($candidate->id ?? 0)));

        return $apiOrdered->concat($missingFromApi)->values();
    }

    public function anonymizeText(string $text): string
    {
        $masked = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text) ?? $text;
        $masked = preg_replace('/(?:\+?\d[\d\s().-]{7,}\d)/', '[phone]', $masked) ?? $masked;

        return $masked;
    }

    /**
     * @return array{normalized: string, tokens: array<int, string>}
     */
    private function preprocessText(string $text): array
    {
        $normalized = strtolower(trim($text));
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[\/_-]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}\s+#.]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $tokens = collect(preg_split('/\s+/', $normalized) ?: [])
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => $token !== '' && !in_array($token, self::STOP_WORDS, true))
            ->map(fn (string $token): string => $this->normalizeSkillLabel($token))
            ->filter(fn (string $token): bool => $token !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'normalized' => $normalized,
            'tokens' => $tokens,
        ];
    }

    /**
     * @param array<int, string> $parsedSkills
     * @param array{normalized: string, tokens: array<int, string>} $candidateProfile
     * @return array<int, string>
     */
    private function extractCandidateSkills(array $parsedSkills, array $candidateProfile): array
    {
        return collect(Arr::get($parsedSkills, 'skills', []))
            ->merge($candidateProfile['tokens'])
            ->filter(fn ($skill) => is_string($skill) && trim($skill) !== '')
            ->map(fn (string $skill): string => $this->normalizeSkillLabel($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $jobSkills
     * @param array<int, string> $candidateSkills
     * @return array{matched: array<int, string>, missing: array<int, string>, skill_score: int}
     */
    private function matchSkills(array $jobSkills, array $candidateSkills): array
    {
        $matched = [];
        $missing = [];
        $scores = [];

        foreach ($jobSkills as $jobSkill) {
            $bestScore = 0.0;

            foreach ($candidateSkills as $candidateSkill) {
                $bestScore = max($bestScore, $this->skillSimilarity($jobSkill, $candidateSkill));
            }

            $scores[] = $bestScore;

            if ($bestScore >= 70) {
                $matched[] = $jobSkill;
                continue;
            }

            $missing[] = $jobSkill;
        }

        $skillScore = count($scores) > 0
            ? (int) round(array_sum($scores) / count($scores))
            : 0;

        return [
            'matched' => array_values(array_unique($matched)),
            'missing' => array_values(array_unique($missing)),
            'skill_score' => max(0, min(100, $skillScore)),
        ];
    }

    /**
     * @param array<int, string> $jobSkills
     * @param array<int, string> $candidateSkills
     * @return array<string, array<string, int>>
     */
    private function buildCategoryBreakdown(array $jobSkills, array $candidateSkills): array
    {
        $categoryWeights = $this->resolveCategoryWeights();
        $taxonomy = $this->buildSkillToCategoryIndex();
        $grouped = [];

        foreach ($jobSkills as $jobSkill) {
            $normalizedJobSkill = $this->normalizeSkillLabel($jobSkill);
            if ($normalizedJobSkill === '') {
                continue;
            }

            $category = $taxonomy[$normalizedJobSkill] ?? 'uncategorized';
            $best = 0.0;
            foreach ($candidateSkills as $candidateSkill) {
                $best = max($best, $this->skillSimilarity($normalizedJobSkill, $candidateSkill));
            }

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'matched' => 0,
                    'total' => 0,
                    'score' => 0,
                    'weight' => $categoryWeights[$category] ?? 0,
                ];
            }

            $grouped[$category]['total']++;
            $grouped[$category]['score'] += (int) round($best);
            if ($best >= 70) {
                $grouped[$category]['matched']++;
            }
        }

        foreach ($grouped as $category => $row) {
            $total = max(1, (int) ($row['total'] ?? 1));
            $grouped[$category]['score'] = (int) round(((int) $row['score']) / $total);
        }

        return $grouped;
    }

    /**
     * @return array<string, string>
     */
    private function buildSkillToCategoryIndex(): array
    {
        $index = [];
        foreach (self::SKILL_TAXONOMY as $category => $skills) {
            foreach ($skills as $skill) {
                $normalized = $this->normalizeSkillLabel($skill);
                if ($normalized !== '') {
                    $index[$normalized] = $category;
                }
            }
        }

        return $index;
    }

    /**
     * @param array<int, string> $jobSkills
     * @return array<int, string>
     */
    private function inferMustHaveSkills(JobPosting $jobPosting, array $jobSkills): array
    {
        $requirements = strtolower((string) $jobPosting->requirements);
        $must = [];

        foreach ($jobSkills as $skill) {
            $normalizedSkill = $this->normalizeSkillLabel($skill);
            if ($normalizedSkill === '') {
                continue;
            }

            foreach (self::HARD_CONSTRAINT_PATTERNS as $token) {
                if (preg_match('/' . preg_quote($token, '/') . '[^.]{0,120}' . preg_quote($normalizedSkill, '/') . '/i', $requirements) === 1) {
                    $must[] = $normalizedSkill;
                    break;
                }
            }
        }

        if ($must === []) {
            $must = collect($jobSkills)
                ->map(fn (string $skill): string => $this->normalizeSkillLabel($skill))
                ->filter(fn (string $skill): bool => $skill !== '')
                ->take(2)
                ->values()
                ->all();
        }

        return array_values(array_unique($must));
    }

    /**
     * @param array<int, string> $mustHaveSkills
     * @param array<int, string> $candidateSkills
     * @return array{must_have: array<int, string>, missing_must_have: array<int, string>, passed: bool, score: int}
     */
    private function evaluateHardConstraints(array $mustHaveSkills, array $candidateSkills): array
    {
        $candidateNormalized = collect($candidateSkills)
            ->map(fn (string $skill): string => $this->normalizeSkillLabel($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->values()
            ->all();

        $missing = [];
        foreach ($mustHaveSkills as $mustHave) {
            $matched = false;
            foreach ($candidateNormalized as $candidateSkill) {
                if ($this->skillSimilarity($mustHave, $candidateSkill) >= 70) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $missing[] = $mustHave;
            }
        }

        $totalMust = count($mustHaveSkills);
        $coverage = $totalMust > 0 ? (int) round(((($totalMust - count($missing)) / $totalMust) * 100)) : 100;

        return [
            'must_have' => array_values($mustHaveSkills),
            'missing_must_have' => array_values($missing),
            'passed' => $missing === [],
            'score' => max(0, min(100, $coverage)),
        ];
    }

    private function skillSimilarity(string $left, string $right): float
    {
        $leftCanonical = $this->normalizeSkillLabel($left);
        $rightCanonical = $this->normalizeSkillLabel($right);

        if ($leftCanonical === '' || $rightCanonical === '') {
            return 0.0;
        }

        if ($leftCanonical === $rightCanonical) {
            return 100.0;
        }

        $leftCompact = str_replace(' ', '', $leftCanonical);
        $rightCompact = str_replace(' ', '', $rightCanonical);

        if ($leftCompact === $rightCompact) {
            return 97.0;
        }

        if (str_contains($leftCompact, $rightCompact) || str_contains($rightCompact, $leftCompact)) {
            return 92.0;
        }

        $leftTokens = array_values(array_unique(array_filter(explode(' ', $leftCanonical))));
        $rightTokens = array_values(array_unique(array_filter(explode(' ', $rightCanonical))));

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $overlap = count(array_intersect($leftTokens, $rightTokens));
        $tokenScore = (2 * $overlap) / (count($leftTokens) + count($rightTokens));

        return round($tokenScore * 100, 1);
    }

    private function scoreExperience(float $years, string $seniority): int
    {
        $expectedYears = match (strtolower(trim($seniority))) {
            'junior' => 2.0,
            'mid' => 5.0,
            'senior' => 8.0,
            'lead' => 10.0,
            default => 5.0,
        };

        if ($expectedYears <= 0.0) {
            return 50;
        }

        return (int) round(max(0, min(100, ($years / $expectedYears) * 100)));
    }

    /**
     * @param array{normalized: string, tokens: array<int, string>} $jobProfile
     * @param array{normalized: string, tokens: array<int, string>} $candidateProfile
     */
    private function scoreEducation(array $jobProfile, array $candidateProfile, ?float $jobGpaRequirement = null, ?float $candidateGpa = null): int
    {
        $jobSignals = $this->extractSignals($jobProfile['normalized'], self::EDUCATION_SIGNALS);
        $candidateSignals = $this->extractSignals($candidateProfile['normalized'], self::EDUCATION_SIGNALS);

        if ($jobGpaRequirement !== null) {
            $signalScore = $this->scoreEducationSignals($jobSignals, $candidateSignals);
            $gpaScore = $candidateGpa !== null
                ? $this->scoreGpaMatch($jobGpaRequirement, $candidateGpa)
                : (count($candidateSignals) > 0 ? 35 : 15);

            if ($jobSignals === []) {
                return $gpaScore;
            }

            return (int) round(($signalScore * 0.65) + ($gpaScore * 0.35));
        }

        return $this->scoreEducationSignals($jobSignals, $candidateSignals);
    }

    /**
     * @param array<int, string> $jobSignals
     * @param array<int, string> $candidateSignals
     */
    private function scoreEducationSignals(array $jobSignals, array $candidateSignals): int
    {
        if ($jobSignals === [] && $candidateSignals === []) {
            return 50;
        }

        if ($jobSignals === []) {
            return count($candidateSignals) > 0 ? 60 : 50;
        }

        $matchedSignals = array_values(array_intersect($jobSignals, $candidateSignals));

        if ($matchedSignals === []) {
            return count($candidateSignals) > 0 ? 40 : 20;
        }

        return (int) round((count($matchedSignals) / count($jobSignals)) * 100);
    }

    private function scoreGpaMatch(float $jobGpaRequirement, ?float $candidateGpa): int
    {
        if ($jobGpaRequirement <= 0.0) {
            return 50;
        }

        if ($candidateGpa === null || $candidateGpa <= 0.0) {
            return 15;
        }

        if ($candidateGpa >= $jobGpaRequirement) {
            return 100;
        }

        return (int) round(max(0, min(100, ($candidateGpa / $jobGpaRequirement) * 100)));
    }

    private function extractGpaFromText(string $text): ?float
    {
        if (trim($text) === '') {
            return null;
        }

        $patterns = [
            '/(?:gpa|cgpa|grade point average)\s*(?:of|:|is|=)?\s*(\d(?:\.\d{1,2})?)(?:\s*\/\s*(\d(?:\.\d{1,2})?))?/i',
            '/(?:minimum|required|min(?:imum)?\.?\s*)?(?:gpa|cgpa|grade point average)\s*(?:of|:|is|=)?\s*(\d(?:\.\d{1,2})?)(?:\s*\/\s*(\d(?:\.\d{1,2})?))?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $value = (float) $matches[1];
            $denominator = isset($matches[2]) ? (float) $matches[2] : null;

            $normalized = $this->normalizeGpaValue($value, $denominator);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeGpaValue(float $value, ?float $denominator = null): ?float
    {
        if ($value <= 0.0) {
            return null;
        }

        if ($denominator !== null && $denominator > 0.0) {
            if ($denominator > 4.5) {
                return round(min(4.0, ($value / $denominator) * 4.0), 2);
            }

            return round(min($denominator, $value), 2);
        }

        if ($value > 4.5) {
            return round(min(4.0, ($value / 10.0) * 4.0), 2);
        }

        return round($value, 2);
    }

    /**
     * @param array<int, string> $signals
     * @return array<int, string>
     */
    private function extractSignals(string $text, array $signals): array
    {
        $normalized = strtolower($text);
        $matched = [];

        foreach ($signals as $signal) {
            $needle = strtolower($signal);
            if (str_contains($normalized, $needle)) {
                $matched[] = $needle;
            }
        }

        return array_values(array_unique($matched));
    }

    private function buildCandidateText(array $parsedCandidate, string $anonymizedText): string
    {
        $summary = (string) Arr::get($parsedCandidate, 'summary', '');
        $skills = implode(' ', array_map('strval', Arr::get($parsedCandidate, 'skills', [])));

        return trim($anonymizedText . ' ' . $summary . ' ' . $skills);
    }

    private function normalizeSkillLabel(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[\/_-]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}+#.\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return '';
        }

        $normalized = self::SKILL_ALIASES[$normalized] ?? $normalized;

        if (str_contains($normalized, ' ')) {
            $compact = str_replace(' ', '', $normalized);
            $normalized = self::SKILL_ALIASES[$compact] ?? $normalized;
        }

        return trim($normalized);
    }

    /**
     * @param array<int, string>|array<string, mixed> $value
     * @return array<int, string>
     */
    private function normalizeSkillOutput(array $value): array
    {
        return collect($value)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => $this->normalizeSkillLabel($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function resolveCategoryWeights(): array
    {
        return self::CATEGORY_WEIGHTS;
    }

    /**
     * @return array<string, int>
     */
    private function resolveWeights(): array
    {
        $configOverrides = [
            'scoring_skills_weight' => config('services.scoring.weights.skills'),
            'scoring_experience_weight' => config('services.scoring.weights.experience'),
            'scoring_education_weight' => config('services.scoring.weights.education'),
        ];

        $weights = [];
        foreach (self::DEFAULT_WEIGHTS as $key => $default) {
            $seed = (int) $default;
            $override = $configOverrides[$key] ?? null;
            if ($override !== null && $override !== '') {
                $weights[$key] = (int) $override;
                continue;
            }

            try {
                $weights[$key] = (int) (AppSetting::getValue($key, (string) $seed) ?? $seed);
            } catch (Throwable) {
                $weights[$key] = $seed;
            }
        }

        $total = array_sum($weights);
        if ($total !== 100 && $total > 0) {
            $weights = [
                'scoring_skills_weight' => (int) round(($weights['scoring_skills_weight'] / $total) * 100),
                'scoring_experience_weight' => (int) round(($weights['scoring_experience_weight'] / $total) * 100),
                'scoring_education_weight' => (int) round(($weights['scoring_education_weight'] / $total) * 100),
            ];
        }

        return $weights;
    }

    /**
     * @param array<string, int> $weights
     */
    private function weightedScore(int $skillScore, int $experienceScore, int $educationScore, array $weights): int
    {
        $score = (
            ($skillScore * ($weights['scoring_skills_weight'] ?? 0)) +
            ($experienceScore * ($weights['scoring_experience_weight'] ?? 0)) +
            ($educationScore * ($weights['scoring_education_weight'] ?? 0))
        ) / 100;

        return (int) round(max(0, min(100, $score)));
    }

    private function callGeminiJson(string $prompt, string $apiKey, ?array $schema = null): ?array
    {
        // A single Gemini call (with "thinking" enabled on 2.5-flash) can take 40s+.
        // PHP's own max_execution_time (often 30s under Apache/mod_php) would otherwise
        // kill this request before the HTTP client's own timeout ever gets a chance to.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $model = (string) config('services.gemini.model', self::DEFAULT_MODEL);
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $generationConfig = [
            'temperature' => 0.25,
            'maxOutputTokens' => 3072,
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
                            ['text' => 'You are an expert ATS recruiter and strict JSON generator for resume screening and ranking. Return JSON only, matching the required schema exactly.'],
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ],
            'ATS screening'
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

        $decoded = $this->parseJsonResponse($text);

        return is_array($decoded) ? $decoded : null;
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

    private function heuristicParse(string $resumeText): array
    {
        $skills = $this->extractKnownSkillsFromText($resumeText);

        preg_match('/([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/i', $resumeText, $emailMatch);
        preg_match('/(?:\+?\d[\d\s().-]{7,}\d)/', $resumeText, $phoneMatch);
        preg_match('/(?:over\s+|more\s+than\s+)?(\d{1,2}(?:\.\d+)?)\s*\+?\s*(?:years?|yrs?)\b/i', $resumeText, $yearsMatch);

        $years = isset($yearsMatch[1]) ? (float) $yearsMatch[1] : 0.0;
        if ($years <= 0.0) {
            $years = $this->estimateYearsFromDateRanges($resumeText);
        }

        return [
            'full_name' => 'Candidate',
            'email' => $emailMatch[1] ?? null,
            'phone' => $phoneMatch[0] ?? null,
            'skills' => $skills,
            'years_of_experience' => $years,
            'summary' => mb_substr($resumeText, 0, 300),
        ];
    }

    private function normalizeParsedProfile(array $payload, string $sourceText): array
    {
        $skillsRaw = Arr::get($payload, 'skills');
        if (!is_array($skillsRaw)) {
            $skillsRaw = Arr::get($payload, 'technical_skills', Arr::get($payload, 'key_skills', Arr::get($payload, 'tech_stack', [])));
        }

        if (!is_array($skillsRaw) && is_string($skillsRaw)) {
            $skillsRaw = preg_split('/[,\n]/', $skillsRaw) ?: [];
        }

        $skills = $this->normalizeSkillOutput(is_array($skillsRaw) ? $skillsRaw : []);
        $extractedSkills = $this->extractKnownSkillsFromText($sourceText);
        $skills = array_values(array_unique(array_merge($skills, $extractedSkills)));
        if ($skills === []) {
            $skills = $extractedSkills;
        }

        $yearsValue = Arr::get($payload, 'years_of_experience', Arr::get($payload, 'experience_years', Arr::get($payload, 'total_experience', 0)));
        $years = is_numeric($yearsValue) ? (float) $yearsValue : 0.0;

        if (!is_numeric($yearsValue) && is_string($yearsValue)) {
            if (preg_match('/(\d{1,2}(?:\.\d+)?)/', $yearsValue, $yearsMatch) === 1) {
                $years = (float) $yearsMatch[1];
            }
        }

        if ($years <= 0.0) {
            if (preg_match('/(?:over\s+|more\s+than\s+)?(\d{1,2}(?:\.\d+)?)\s*\+?\s*(?:years?|yrs?)\b/i', $sourceText, $yearsMatch) === 1) {
                $years = (float) $yearsMatch[1];
            }
        }

        if ($years <= 0.0) {
            $years = $this->estimateYearsFromDateRanges($sourceText);
        }

        preg_match('/([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/i', $sourceText, $emailMatch);
        preg_match('/(?:\+?\d[\d\s().-]{7,}\d)/', $sourceText, $phoneMatch);

        $gpaValue = Arr::get($payload, 'gpa');
        $gpa = is_numeric($gpaValue) ? max(0.0, min(4.0, round((float) $gpaValue, 2))) : $this->extractGpaFromText($sourceText);

        return [
            'full_name' => (string) Arr::get($payload, 'full_name', Arr::get($payload, 'name', Arr::get($payload, 'candidate_name', 'Candidate'))),
            'email' => Arr::get($payload, 'email', $emailMatch[1] ?? null),
            'phone' => Arr::get($payload, 'phone', $phoneMatch[0] ?? null),
            'skills' => $skills,
            'years_of_experience' => max(0, $years),
            'gpa' => $gpa,
            'summary' => (string) Arr::get($payload, 'summary', Arr::get($payload, 'profile_summary', mb_substr($sourceText, 0, 300))),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractKnownSkillsFromText(string $text): array
    {
        $normalized = $this->preprocessText($text)['normalized'];

        return collect(self::KNOWN_SKILLS)
            ->map(fn (string $skill): string => $this->normalizeSkillLabel($skill))
            ->filter(fn (string $skill): bool => $skill !== '')
            ->filter(function (string $skill) use ($normalized): bool {
                $pattern = '/\b' . preg_quote($skill, '/') . '\b/i';

                return preg_match($pattern, $normalized) === 1;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function estimateYearsFromDateRanges(string $text): float
    {
        $patterns = [
            '/\b((?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\s+\d{4})\s*(?:-|to|until|through|\s+)\s*(present|current|now|(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\s+\d{4}|\d{4})\b/i',
            '/\b(\d{1,2}[\/-]\d{4})\s*(?:-|to|until|through|\s+)\s*(present|current|now|\d{1,2}[\/-]\d{4})\b/i',
            '/\b(19\d{2}|20\d{2})\s*(?:-|to|until|through|\s+)\s*(present|current|now|19\d{2}|20\d{2})\b/i',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $patternMatches, PREG_SET_ORDER);
            $matches = array_merge($matches, $patternMatches);
        }

        if ($matches === []) {
            return 0.0;
        }

        $currentYear = (int) date('Y');
        $bestSpan = 0.0;

        foreach ($matches as $range) {
            $startDate = $this->parseApproximateDateToken((string) ($range[1] ?? ''));
            $endRaw = strtolower((string) ($range[2] ?? ''));
            $endDate = in_array($endRaw, ['present', 'current', 'now'], true)
                ? new \DateTimeImmutable($currentYear . '-12-31')
                : $this->parseApproximateDateToken((string) ($range[2] ?? ''));

            if ($startDate === null || $endDate === null || $endDate < $startDate) {
                continue;
            }

            $span = ((int) $startDate->diff($endDate)->format('%r%a')) / 365.25;
            $bestSpan = max($bestSpan, $span);
        }

        return max(0.0, min(40.0, $bestSpan));
    }

    private function parseApproximateDateToken(string $value): ?\DateTimeImmutable
    {
        $candidate = trim($value);
        if ($candidate === '') {
            return null;
        }

        $attempts = array_values(array_unique([
            $candidate,
            ucwords($candidate),
            ucfirst($candidate),
        ]));

        foreach (['!F Y', '!M Y', '!n/Y', '!m/Y', '!Y'] as $format) {
            foreach ($attempts as $attempt) {
                $date = \DateTimeImmutable::createFromFormat($format, $attempt);
                if ($date instanceof \DateTimeImmutable) {
                    return $date;
                }
            }
        }

        $timestamp = strtotime($candidate);
        if ($timestamp !== false) {
            return (new \DateTimeImmutable())->setTimestamp($timestamp);
        }

        return null;
    }

    private function extractSkills(JobPosting $jobPosting): array
    {
        $jobContext = implode(' ', [
            (string) $jobPosting->title,
            (string) $jobPosting->department,
            (string) $jobPosting->about,
            (string) $jobPosting->responsibilities,
            (string) $jobPosting->requirements,
            (string) $jobPosting->skills_text,
        ]);

        $fromJson = collect($jobPosting->skills_json ?? [])
            ->filter(fn ($skill) => is_string($skill) && trim($skill) !== '')
            ->map(fn ($skill) => $this->normalizeSkillLabel((string) $skill))
            ->filter(fn ($skill) => $skill !== '');

        $fromText = collect(preg_split('/[,\n]/', (string) $jobPosting->skills_text) ?: [])
            ->map(fn ($skill) => $this->normalizeSkillLabel((string) $skill))
            ->filter(fn ($skill) => $skill !== '');

        $fromContext = collect($this->extractKnownSkillsFromText($jobContext))
            ->map(fn (string $skill): string => $this->normalizeSkillLabel($skill))
            ->filter(fn (string $skill): bool => $skill !== '');

        return $fromJson
            ->merge($fromText)
            ->merge($fromContext)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, int|array<string, int>> $breakdown
     */
    private function buildScorePayload(int $score, array $matched, array $missing, array $breakdown = []): array
    {
        $score = max(0, min(100, $score));

        return [
            'match_score' => $score,
            'matched_skills' => $matched,
            'missing_skills' => $missing,
            'overall_assessment' => $this->recommendationFromScore($score),
            'recommendation' => $this->recommendationFromScore($score),
            'score_breakdown' => $breakdown,
        ];
    }

    private function recommendationFromScore(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Shortlisted',
            $score >= 40 => 'Review',
            default => 'Rejected',
        };
    }

    private function jsonForPrompt(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
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
