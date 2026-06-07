<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiScore;
use App\Models\Application;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function recruitmentSummary(Request $request): JsonResponse
    {
        $totalApplicants = Application::count();
        $shortlistedCandidates = Application::query()->where('status', 'shortlisted')->count();

        $statusTrends = Application::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $qualificationAnalysis = Application::query()
            ->join('applicants', 'applicants.id', '=', 'applications.applicant_id')
            ->join('education', 'education.applicant_id', '=', 'applicants.id')
            ->select('education.level', DB::raw('COUNT(*) as total'))
            ->groupBy('education.level')
            ->pluck('total', 'education.level');

        $aiStats = AiScore::query()
            ->select(
                DB::raw('AVG(match_percentage) as avg_score'),
                DB::raw('MAX(match_percentage) as max_score'),
                DB::raw('MIN(match_percentage) as min_score'),
            )
            ->first();

        $genderDistribution = Application::query()
            ->join('applicants', 'applicants.id', '=', 'applications.applicant_id')
            ->select('applicants.gender', DB::raw('COUNT(*) as total'))
            ->groupBy('applicants.gender')
            ->pluck('total', 'applicants.gender');

        $payload = [
            'total_applicants' => $totalApplicants,
            'shortlisted_candidates' => $shortlistedCandidates,
            'recruitment_trends' => $statusTrends,
            'qualification_analysis' => $qualificationAnalysis,
            'ai_scoring_statistics' => $aiStats,
            'gender_distribution' => $genderDistribution,
        ];

        $report = Report::query()->create([
            'generated_by' => $request->user()?->id,
            'type' => 'recruitment_summary',
            'payload' => $payload,
            'generated_at' => now(),
        ]);

        return response()->json([
            'report_id' => $report->id,
            'data' => $payload,
        ]);
    }
}
