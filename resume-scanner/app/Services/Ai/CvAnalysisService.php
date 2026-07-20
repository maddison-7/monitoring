<?php

namespace App\Services\Ai;

use App\Models\Applicant;

class CvAnalysisService
{
    private const SKILL_SYNONYMS = [
        'software engineer' => 'developer',
        'software developer' => 'developer',
        'full stack engineer' => 'full stack developer',
        'backend engineer' => 'backend developer',
        'frontend engineer' => 'frontend developer',
        'human resources' => 'hr',
        'talent acquisition' => 'recruitment',
        'js' => 'javascript',
        'nodejs' => 'node.js',
        'node js' => 'node.js',
        'reactjs' => 'react',
        'react js' => 'react',
        'vuejs' => 'vue',
        'vue js' => 'vue',
        'angularjs' => 'angular',
        'ts' => 'typescript',
        'py' => 'python',
        'c sharp' => 'c#',
        'csharp' => 'c#',
        'dotnet' => '.net',
        'asp.net' => '.net',
        'postgres' => 'postgresql',
        'postgres sql' => 'postgresql',
        'mysql database' => 'mysql',
        'ms sql' => 'sql server',
        'mssql' => 'sql server',
        'rest api' => 'api',
        'restful api' => 'api',
        'restful' => 'api',
        'graph ql' => 'graphql',
        'amazon web services' => 'aws',
        'google cloud platform' => 'gcp',
        'google cloud' => 'gcp',
        'microsoft azure' => 'azure',
        'ms excel' => 'excel',
        'microsoft excel' => 'excel',
        'microsoft word' => 'word',
        'microsoft office' => 'ms office',
        'power point' => 'powerpoint',
        'power bi' => 'powerbi',
        'search engine optimization' => 'seo',
        'search engine marketing' => 'sem',
        'google ads' => 'ppc',
        'pay per click' => 'ppc',
        'social media marketing' => 'social media',
        'content management system' => 'cms',
        'customer relationship management' => 'crm',
        'enterprise resource planning' => 'erp',
        'supply chain management' => 'supply chain',
        'learning management system' => 'lms',
        'project management' => 'project management',
        'human resource management' => 'hr',
        'employee relations management' => 'employee relations',
        'accounts payable and receivable' => 'accounts payable',

        // Discipline/field-level synonyms — distinct from the job-title synonyms above
        // (e.g. "software engineer" -> "developer"), these cover cases where a candidate
        // or job posting names the *field* rather than a role: "Software Engineering" and
        // "Software Development" describe the same discipline but share too few tokens
        // for the fuzzy token-overlap scorer to catch on its own (see skillSimilarity()).
        'software engineering' => 'software development',
        'web application development' => 'web development',
        'mobile application development' => 'mobile development',
        'app development' => 'mobile development',
        'data analytics' => 'data analysis',
        'business administration' => 'business management',
        'customer support' => 'customer service',
        'client service' => 'customer service',
        'quality assurance' => 'qa',
        'software testing' => 'qa',
        'information technology' => 'it',
        'information systems' => 'it',
        'ux design' => 'ui ux design',
        'ui design' => 'ui ux design',
    ];

    private const MIN_SKILL_MATCH_SCORE = 72.0;

    /**
     * Highest tier first — classifyEducationTier() returns the max tier whose
     * keywords appear anywhere in the text, so a transcript mentioning both a
     * bachelor's and a master's is correctly classified as tier 3.
     */
    private const EDUCATION_TIERS = [
        4 => ['phd', 'ph.d', 'ph d', 'doctorate', 'doctoral'],
        3 => ['master', 'masters', "master's", 'msc', 'm.sc', 'ma', 'm.a', 'mba', 'meng', 'm.eng', 'mtech', 'm.tech', 'llm'],
        2 => ['bachelor', 'bachelors', "bachelor's", 'bsc', 'b.sc', 'ba', 'b.a', 'beng', 'b.eng', 'btech', 'b.tech', 'bs'],
        1 => ['diploma', 'associate degree', 'higher national diploma', 'hnd'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function analyzeApplicant(Applicant $applicant): array
    {
        $parsedCv = app(CvContentExtractionService::class)->parse((string) ($applicant->cv_text ?? ''));

        $skillNames = $applicant->skills->pluck('name')->map(fn (string $name): string => $this->normalizeSkill($name))->values()->all();
        $skillNames = array_values(array_unique(array_merge($skillNames, (array) ($parsedCv['skills'] ?? []))));

        $education = $applicant->educations->map(fn ($item): array => [
            'level' => $item->level,
            'field' => $item->field_of_study,
            'institution' => $item->institution,
        ])->values()->all();

        foreach ((array) ($parsedCv['education'] ?? []) as $signal) {
            $education[] = [
                'level' => (string) $signal,
                'field' => null,
                'institution' => null,
            ];
        }

        $certifications = $applicant->certificates->map(fn ($item): array => [
            'name' => $item->name,
            'issuer' => $item->issuer,
        ])->values()->all();

        foreach ((array) ($parsedCv['certifications'] ?? []) as $cert) {
            $certifications[] = [
                'name' => (string) $cert,
                'issuer' => null,
            ];
        }

        $structuredExperienceYears = $this->estimateYearsExperience($applicant);
        $cvTextExperienceYears = (float) ($parsedCv['experience_years'] ?? 0.0);
        $experienceYears = max($structuredExperienceYears, $cvTextExperienceYears);

        $projects = $applicant->projects->map(fn ($item): array => [
            'title' => $item->title,
            'description' => $item->description,
            'technologies' => $item->technologies,
        ])->values()->all();

        $achievements = $applicant->achievements->map(fn ($item): array => [
            'title' => $item->title,
            'description' => $item->description,
        ])->values()->all();

        return [
            'skills' => array_values(array_unique($skillNames)),
            'education' => $education,
            'certifications' => $certifications,
            'experience_years' => $experienceYears,
            'cv_experience_signals' => $parsedCv['experience'] ?? [],
            'gpa' => $applicant->gpa !== null ? (float) $applicant->gpa : ($parsedCv['gpa'] ?? null),
            'projects' => $projects,
            'achievements' => $achievements,
        ];
    }

    public function normalizeSkill(string $skill): string
    {
        $value = strtolower(trim($skill));
        $value = preg_replace('/[\/_-]+/', ' ', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}+#.\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = self::SKILL_SYNONYMS[$value] ?? $value;

        if (str_contains($value, ' ')) {
            $compact = str_replace(' ', '', $value);
            $value = self::SKILL_SYNONYMS[$compact] ?? $value;
        }

        return $value;
    }

    /**
     * Fuzzy-compares two already-normalized skill labels and returns a 0-100
     * confidence score, so that e.g. "React" and "reactjs" or "Node.js" and
     * "node js" are recognised as the same skill instead of being missed by
     * a strict equality check.
     */
    public function skillSimilarity(string $left, string $right): float
    {
        $leftCanonical = $this->normalizeSkill($left);
        $rightCanonical = $this->normalizeSkill($right);

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

        if ($this->hasWordBoundaryContainment($leftCanonical, $rightCanonical)) {
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

    /**
     * Whole-word containment check between two already-normalized (space-preserving)
     * skill labels, so "cloud" matches inside "cloud computing" but "go" does not
     * match inside "django" and "java" does not match inside "javascript" — the
     * previous compact-substring check had no word boundaries and produced exactly
     * those kinds of false positives.
     */
    private function hasWordBoundaryContainment(string $left, string $right): bool
    {
        [$shorter, $longer] = mb_strlen($left) <= mb_strlen($right) ? [$left, $right] : [$right, $left];

        if ($shorter === '') {
            return false;
        }

        $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($shorter, '/') . '(?![\p{L}\p{N}])/u';

        return preg_match($pattern, $longer) === 1;
    }

    public function isSkillMatch(string $left, string $right): bool
    {
        return $this->skillSimilarity($left, $right) >= self::MIN_SKILL_MATCH_SCORE;
    }

    /**
     * Classifies free text into the highest education tier it mentions
     * (0 none, 1 diploma/associate, 2 bachelor, 3 master, 4 phd), so job
     * qualifications and candidate education rows can be compared on a
     * common scale instead of only checking for the word "bachelor".
     */
    public function classifyEducationTier(string $text): int
    {
        $normalized = strtolower(trim($text));

        if ($normalized === '') {
            return 0;
        }

        foreach (self::EDUCATION_TIERS as $tier => $keywords) {
            foreach ($keywords as $keyword) {
                $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($keyword, '/') . '(?![\p{L}\p{N}])/u';
                if (preg_match($pattern, $normalized) === 1) {
                    return $tier;
                }
            }
        }

        return 0;
    }

    private function estimateYearsExperience(Applicant $applicant): float
    {
        $years = 0.0;

        foreach ($applicant->experiences as $experience) {
            if (!$experience->start_date) {
                continue;
            }

            $endDate = $experience->is_current
                ? now()->toDateString()
                : (string) ($experience->end_date?->toDateString() ?? now()->toDateString());

            $days = $experience->start_date->diffInDays($endDate);
            $years += $days / 365.25;
        }

        return round($years, 2);
    }
}
