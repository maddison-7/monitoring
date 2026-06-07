<?php

namespace App\Services\Ai;

class CvContentExtractionService
{
    private const KNOWN_SKILLS = [
        'php', 'laravel', 'mysql', 'postgresql', 'sql', 'javascript', 'typescript', 'react', 'vue',
        'python', 'java', 'c#', 'excel', 'power bi', 'tableau', 'project management', 'recruitment',
        'data analysis', 'monitoring and evaluation', 'procurement', 'financial reporting',
    ];

    /**
     * @return array{skills: array<int, string>, education: array<int, string>, experience: array<int, string>, certifications: array<int, string>}
     */
    public function parse(string $text): array
    {
        $normalized = strtolower(preg_replace('/\s+/', ' ', $text) ?? $text);

        $skills = collect(self::KNOWN_SKILLS)
            ->filter(fn (string $skill): bool => str_contains($normalized, strtolower($skill)))
            ->values()
            ->all();

        $education = $this->extractByKeywords($normalized, [
            'bachelor', 'master', 'phd', 'diploma', 'certificate',
        ]);

        $experience = $this->extractByRegex($text, [
            '/\b\d{1,2}\+?\s*(?:years?|yrs?)\b/i',
            '/\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{4}\s*(?:-|to)\s*(?:present|current|now|(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{4}|\d{4})\b/i',
        ]);

        $certifications = $this->extractByKeywords($normalized, [
            'certified', 'certification', 'certificate', 'aws', 'pmp', 'ccna',
        ]);

        return [
            'skills' => array_values(array_unique($skills)),
            'education' => $education,
            'experience' => $experience,
            'certifications' => $certifications,
        ];
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
