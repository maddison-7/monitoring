<?php

namespace App\Services\Ai;

use App\Models\Applicant;
use App\Models\Job;

class RecruitmentScoringService
{
    public function __construct(private readonly CvAnalysisService $cvAnalysisService)
    {
    }

    /**
     * @return array{match_percentage: float, recommendation_level: string, matched_skills: array<int, string>, missing_skills: array<int, string>, summary: string}
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
            ->filter(fn (string $skill): bool => $candidateSkills->contains($skill))
            ->values()
            ->all();

        $missing = $requiredSkills
            ->reject(fn (string $skill): bool => in_array($skill, $matched, true))
            ->values()
            ->all();

        $skillsScore = $requiredSkills->count() > 0
            ? (($requiredSkills->count() - count($missing)) / $requiredSkills->count()) * 100
            : 50;

        $experienceYears = (float) ($analysis['experience_years'] ?? 0.0);
        $experienceTarget = max(1, (int) ($job->min_years_experience ?? 1));
        $experienceScore = min(100, ($experienceYears / $experienceTarget) * 100);

        $eduLevelRequired = strtolower((string) $job->qualifications);
        $educationScore = 50.0;

        if (str_contains($eduLevelRequired, 'bachelor')) {
            $hasBachelor = collect((array) ($analysis['education'] ?? []))
                ->contains(fn (array $row): bool => str_contains(strtolower((string) ($row['level'] ?? '')), 'bachelor'));
            $educationScore = $hasBachelor ? 100.0 : 25.0;
        }

        $matchPercentage = round(($skillsScore * 0.55) + ($experienceScore * 0.30) + ($educationScore * 0.15), 2);
        $recommendation = $this->recommendationLevel($matchPercentage);

        return [
            'match_percentage' => $matchPercentage,
            'recommendation_level' => $recommendation,
            'matched_skills' => $matched,
            'missing_skills' => $missing,
            'summary' => 'AI screening is decision-support only. Final decision remains with HR.',
        ];
    }

    private function recommendationLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Shortlisted',
            $score >= 45 => 'Review',
            default => 'Rejected',
        };
    }
}
