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

        $experienceYears = $this->estimateYearsExperience($applicant);

        return [
            'skills' => array_values(array_unique($skillNames)),
            'education' => $education,
            'certifications' => $certifications,
            'experience_years' => $experienceYears,
            'cv_experience_signals' => $parsedCv['experience'] ?? [],
        ];
    }

    public function normalizeSkill(string $skill): string
    {
        $value = strtolower(trim($skill));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return self::SKILL_SYNONYMS[$value] ?? $value;
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
