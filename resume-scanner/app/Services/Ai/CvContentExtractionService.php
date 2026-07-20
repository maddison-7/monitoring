<?php

namespace App\Services\Ai;

class CvContentExtractionService
{
    private const KNOWN_SKILLS = [
        // Languages & core tech
        'php', 'laravel', 'symfony', 'mysql', 'postgresql', 'sql', 'sql server', 'sqlite', 'redis',
        'mongodb', 'javascript', 'typescript', 'react', 'vue', 'angular', 'node.js', 'express',
        'python', 'django', 'flask', 'fastapi', 'java', 'spring', 'c#', 'c++', '.net', 'ruby',
        'go', 'rust', 'swift', 'kotlin', 'html', 'css', 'sass', 'tailwind', 'bootstrap',
        'docker', 'kubernetes', 'aws', 'azure', 'gcp', 'git', 'linux', 'api', 'rest', 'graphql',
        'ci/cd', 'jenkins', 'terraform',
        // Office & analytics tools
        'excel', 'microsoft excel', 'microsoft word', 'microsoft office', 'ms office', 'powerpoint',
        'figma', 'power bi', 'tableau', 'google analytics', 'google sheets', 'canva', 'photoshop',
        // Soft skills / general ops
        'communication', 'teamwork', 'leadership', 'problem solving', 'time management',
        'customer service', 'data entry', 'report writing', 'project management', 'data analysis',
        'critical thinking', 'negotiation', 'presentation', 'adaptability', 'multitasking',
        // HR
        'recruitment', 'hr', 'human resources', 'onboarding', 'payroll processing', 'employee relations',
        'performance management', 'benefits administration', 'labor law', 'talent acquisition',
        // Marketing
        'seo', 'sem', 'content marketing', 'social media', 'email marketing', 'ppc', 'brand management',
        'copywriting', 'market research', 'digital marketing',
        // Legal
        'contract drafting', 'legal research', 'litigation support', 'compliance', 'case management',
        'intellectual property', 'corporate law',
        // Logistics / procurement
        'inventory management', 'supply chain', 'warehouse operations', 'procurement', 'fleet management',
        'shipping', 'logistics coordination', 'monitoring and evaluation',
        // Education / training
        'lesson planning', 'curriculum development', 'classroom management', 'student assessment',
        'lms', 'training delivery',
        // Hospitality
        'front desk', 'reservation systems', 'housekeeping', 'food safety', 'event coordination',
        'guest relations',
        // Finance
        'bookkeeping', 'financial reporting', 'tax preparation', 'auditing', 'accounts payable',
        'accounts receivable', 'budgeting', 'forecasting',
    ];

    private const SKILL_ALIASES = [
        'js' => 'javascript',
        'nodejs' => 'node.js',
        'reactjs' => 'react',
        'vuejs' => 'vue',
        'ts' => 'typescript',
        'py' => 'python',
        'csharp' => 'c#',
        'dotnet' => '.net',
        'postgres' => 'postgresql',
        'msoffice' => 'ms office',
        'powerbi' => 'power bi',
    ];

    private const MONTH_NAMES = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';

    /**
     * Many real CVs list work history under bare year-only ranges ("2015 - 2016") that
     * are lexically identical to education/institution attendance dates elsewhere in the
     * same document — the only thing that disambiguates them is the section heading they
     * fall under. So bare-year ranges are only trusted for experience when they appear
     * after one of these headings (see extractExperienceSectionText()).
     */
    private const EXPERIENCE_SECTION_HEADINGS = [
        'work experience', 'working experience', 'employment history', 'professional experience',
        'career history', 'employment record', 'work history', 'practical experience',
    ];

    private const SECTION_END_HEADINGS = [
        'education', 'academic', 'skills', 'referees', 'reference', 'hobbies', 'interests',
        'achievements', 'certifications', 'declaration', 'other experience', 'languages',
        'personal details', 'training', 'projects',
    ];

    /**
     * @return array{skills: array<int, string>, education: array<int, string>, experience: array<int, string>, experience_years: float, certifications: array<int, string>, gpa: float|null}
     */
    public function parse(string $text): array
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', $text) ?? $text);

        $skills = collect(self::KNOWN_SKILLS)
            ->filter(fn (string $skill): bool => $this->containsWholeSkill($normalized, $skill))
            ->values()
            ->all();

        $education = $this->extractByKeywords($normalized, [
            'bachelor', 'master', 'phd', 'diploma', 'certificate',
        ]);

        $experience = $this->extractByRegex($text, [
            '/\b\d{1,2}\+?\s*(?:years?|yrs?)\b/i',
            '/\b(?:' . self::MONTH_NAMES . ')[a-z]*\s+\d{4}\s*(?:-|to)\s*(?:present|current|now|(?:' . self::MONTH_NAMES . ')[a-z]*\s+\d{4}|\d{4})\b/i',
        ]);

        $certifications = $this->extractByKeywords($normalized, [
            'certified', 'certification', 'certificate', 'aws', 'pmp', 'ccna',
        ]);

        return [
            'skills' => array_values(array_unique($skills)),
            'education' => $education,
            'experience' => $experience,
            'experience_years' => $this->estimateExperienceYears($text),
            'certifications' => $certifications,
            'gpa' => $this->extractGpaFromText($text),
        ];
    }

    /**
     * Estimates total years of work experience directly from free-text CV content, so
     * candidates who never filled in the separate structured "work experience" profile
     * section (common when someone just uploads a CV file) still get credit for the
     * experience actually described in their CV. Two independent heuristics are tried
     * and the larger is kept:
     *  1. If a work-experience style heading is found (see extractExperienceSectionText()),
     *     sum every date range — month-year ("March 2021 to June 2021") or bare year
     *     ("2015 - 2016") — found only within that section. Some CV templates use
     *     month-year ranges for education dates too, so month ranges are only trusted
     *     globally when no section could be isolated (case 2 below); otherwise they'd
     *     double-count education duration as work experience.
     *  2. If no such heading is found at all, fall back to a full-text scan for
     *     month-year ranges only — bare years are too ambiguous with education dates to
     *     trust without section context.
     *  3. The largest explicit "N years"/"N+ years" statement anywhere in the text is
     *     always considered as well.
     */
    public function estimateExperienceYears(string $text): float
    {
        $statedYears = $this->maxStatedYears($text);
        $sectionText = $this->extractExperienceSectionText($text);

        if ($sectionText !== '') {
            $sectionYears = max($this->sumDateRangeYears($sectionText), $this->sumBareYearRangeYears($sectionText));

            return max($sectionYears, $statedYears);
        }

        return max($this->sumDateRangeYears($text), $statedYears);
    }

    /**
     * Returns the text between the first work-experience style heading and the next
     * heading that plausibly starts a different section, so bare year ranges within it
     * can be safely treated as job tenure rather than education dates.
     */
    private function extractExperienceSectionText(string $text): string
    {
        $lower = strtolower($text);

        $startPos = null;
        foreach (self::EXPERIENCE_SECTION_HEADINGS as $heading) {
            $pos = strpos($lower, $heading);
            if ($pos !== false && ($startPos === null || $pos < $startPos)) {
                $startPos = $pos + strlen($heading);
            }
        }

        if ($startPos === null) {
            return '';
        }

        $endPos = strlen($text);
        foreach (self::SECTION_END_HEADINGS as $heading) {
            $pos = strpos($lower, $heading, $startPos);
            if ($pos !== false && $pos < $endPos) {
                $endPos = $pos;
            }
        }

        return substr($text, $startPos, max(0, $endPos - $startPos));
    }

    private function sumBareYearRangeYears(string $sectionText): float
    {
        // Extracted CV text often has no whitespace between a table cell's year and the
        // next cell's text (e.g. "TASK2015 - 2016AIRTEL"), so digits directly touching a
        // letter must still match — \b sees no boundary between a letter and a digit
        // (both are \w), which silently dropped exactly these real cases. The digit
        // group itself is still bounded so a 4-digit chunk is never grabbed out of a
        // longer number (e.g. a phone number).
        if (preg_match_all(
            '/(?<!\d)(\d{4})(?!\d)\s*(?:-|\x{2013}|\bto\b)\s*(present|current|now|(?<!\d)\d{4}(?!\d))/iu',
            $sectionText,
            $matches,
            PREG_SET_ORDER
        ) === false) {
            return 0.0;
        }

        $nowYear = (int) now()->format('Y');
        $totalMonths = 0;

        foreach ($matches as $match) {
            $startYear = (int) $match[1];
            if ($startYear < 1950 || $startYear > $nowYear + 1) {
                continue;
            }

            $endToken = strtolower($match[2]);
            $endYear = in_array($endToken, ['present', 'current', 'now'], true) ? $nowYear : (int) $match[2];

            $years = $endYear - $startYear;
            if ($years > 0 && $years < 50) {
                $totalMonths += $years * 12;
            }
        }

        return round($totalMonths / 12, 2);
    }

    private function sumDateRangeYears(string $text): float
    {
        $totalMonths = 0;

        if (preg_match_all(
            '/\b(' . self::MONTH_NAMES . ')[a-z]*\.?\s*(?<!\d)(\d{4})(?!\d)\s*(?:-|\x{2013}|\bto\b)\s*(?:present|current|now)\b/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            $nowMonth = (int) now()->format('n');
            $nowYear = (int) now()->format('Y');
            foreach ($matches as $match) {
                $totalMonths += $this->monthsBetween($match[1], (int) $match[2], $nowMonth, $nowYear);
            }
        }

        if (preg_match_all(
            '/\b(' . self::MONTH_NAMES . ')[a-z]*\.?\s*(?<!\d)(\d{4})(?!\d)\s*(?:-|\x{2013}|\bto\b)\s*(' . self::MONTH_NAMES . ')[a-z]*\.?\s*(?<!\d)(\d{4})(?!\d)/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $endMonth = $this->monthNumber($match[3]);
                if ($endMonth !== null) {
                    $totalMonths += $this->monthsBetween($match[1], (int) $match[2], $endMonth, (int) $match[4]);
                }
            }
        }

        return round(max(0, $totalMonths) / 12, 2);
    }

    private function monthsBetween(string $startMonthName, int $startYear, int $endMonth, int $endYear): int
    {
        $startMonth = $this->monthNumber($startMonthName);
        if ($startMonth === null || $startYear < 1950 || $startYear > (int) now()->format('Y') + 1) {
            return 0;
        }

        $months = (($endYear - $startYear) * 12) + ($endMonth - $startMonth);

        return $months > 0 && $months < 600 ? $months : 0;
    }

    private function monthNumber(string $name): ?int
    {
        static $map = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        return $map[strtolower(substr($name, 0, 3))] ?? null;
    }

    private function maxStatedYears(string $text): float
    {
        if (preg_match_all('/\b(\d{1,2})\+?\s*(?:years?|yrs?)\b/i', $text, $matches) && ($matches[1] ?? []) !== []) {
            return (float) min(45, max(array_map('intval', $matches[1])));
        }

        return 0.0;
    }

    /**
     * Extracts a candidate's own GPA directly from free-text CV content (e.g.
     * "GPA: 3.5", "CGPA of 3.7/4.0"), so candidates who never filled in the separate
     * structured GPA profile field still get a GPA signal when their CV states one.
     */
    public function extractGpaFromText(string $text): ?float
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

    private function containsWholeSkill(string $normalizedText, string $skill): bool
    {
        $needle = strtolower($skill);
        $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u';

        if (preg_match($pattern, $normalizedText) === 1) {
            return true;
        }

        foreach (self::SKILL_ALIASES as $alias => $canonical) {
            if ($canonical !== $needle) {
                continue;
            }

            $aliasPattern = '/(?<![\p{L}\p{N}])' . preg_quote($alias, '/') . '(?![\p{L}\p{N}])/u';
            if (preg_match($aliasPattern, $normalizedText) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $keywords
     * @return array<int, string>
     */
    private function extractByKeywords(string $text, array $keywords): array
    {
        return collect($keywords)
            ->filter(fn (string $keyword): bool => str_contains($text, strtolower($keyword)))
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $patterns
     * @return array<int, string>
     */
    private function extractByRegex(string $text, array $patterns): array
    {
        $results = [];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            foreach (($matches[0] ?? []) as $match) {
                $results[] = trim((string) $match);
            }
        }

        return array_values(array_unique(array_filter($results)));
    }
}
