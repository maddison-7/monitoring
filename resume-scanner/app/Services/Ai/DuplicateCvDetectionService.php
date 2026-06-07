<?php

namespace App\Services\Ai;

use App\Models\Applicant;

class DuplicateCvDetectionService
{
    /**
     * @return array{is_duplicate: bool, reason: string|null, matched_applicant_id: int|null, similarity: float}
     */
    public function detect(Applicant $applicant, string $cvHash, string $cvText): array
    {
        $exact = Applicant::query()
            ->where('id', '!=', $applicant->id)
            ->where('cv_hash', $cvHash)
            ->first();

        if ($exact instanceof Applicant) {
            return [
                'is_duplicate' => true,
                'reason' => 'Exact duplicate CV detected.',
                'matched_applicant_id' => (int) $exact->id,
                'similarity' => 100.0,
            ];
        }

        $fingerprint = $this->buildFingerprint($cvText);
        if ($fingerprint === []) {
            return [
                'is_duplicate' => false,
                'reason' => null,
                'matched_applicant_id' => null,
                'similarity' => 0.0,
            ];
        }

        $candidates = Applicant::query()
            ->where('id', '!=', $applicant->id)
            ->whereNotNull('cv_fingerprint')
            ->get(['id', 'cv_fingerprint']);

        $bestSimilarity = 0.0;
        $bestApplicantId = null;

        foreach ($candidates as $candidate) {
            $other = $this->parseFingerprint((string) $candidate->cv_fingerprint);
            $similarity = $this->jaccardSimilarity($fingerprint, $other);

            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestApplicantId = (int) $candidate->id;
            }
        }

        if ($bestSimilarity >= 90.0) {
            return [
                'is_duplicate' => true,
                'reason' => 'Highly similar CV detected.',
                'matched_applicant_id' => $bestApplicantId,
                'similarity' => round($bestSimilarity, 2),
            ];
        }

        return [
            'is_duplicate' => false,
            'reason' => null,
            'matched_applicant_id' => null,
            'similarity' => round($bestSimilarity, 2),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function buildFingerprint(string $text): array
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? $normalized;

        $tokens = collect(explode(' ', $normalized))
            ->filter(fn (string $token): bool => mb_strlen($token) >= 4)
            ->take(300)
            ->unique()
            ->values()
            ->all();

        return $tokens;
    }

    /**
     * @param array<int, string> $left
     * @param array<int, string> $right
     */
    private function jaccardSimilarity(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($left, $right));
        $union = count(array_unique(array_merge($left, $right)));

        if ($union === 0) {
            return 0.0;
        }

        return ($intersection / $union) * 100;
    }

    /**
     * @return array<int, string>
     */
    private function parseFingerprint(string $value): array
    {
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), fn (string $token): bool => $token !== ''));
    }
}
