<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiScore;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $jobIds = Job::query()
            ->where('hr_officer_id', $user->id)
            ->pluck('id');

        $totalApplicants = Application::query()->whereIn('job_id', $jobIds)->count();
        $shortlisted = Application::query()->whereIn('job_id', $jobIds)->where('status', 'shortlisted')->count();

        $topRanked = Application::query()
            ->whereIn('job_id', $jobIds)
            ->with(['applicant.user:id,name,email', 'job:id,title', 'aiScore'])
            ->whereHas('aiScore')
            ->orderByDesc(
                AiScore::query()
                    ->select('match_percentage')
                    ->whereColumn('ai_scores.application_id', 'applications.id')
                    ->limit(1)
            )
            ->limit(10)
            ->get();

        $recommendationStats = AiScore::query()
            ->join('applications', 'applications.id', '=', 'ai_scores.application_id')
            ->whereIn('applications.job_id', $jobIds)
            ->select('recommendation_level', DB::raw('COUNT(*) as total'))
            ->groupBy('recommendation_level')
            ->pluck('total', 'recommendation_level');

        $analytics = [
            'total_applicants' => $totalApplicants,
            'shortlisted' => $shortlisted,
            'hired' => Application::query()->whereIn('job_id', $jobIds)->where('status', 'hired')->count(),
            'rejected' => Application::query()->whereIn('job_id', $jobIds)->whereIn('status', ['rejected', 'auto_rejected'])->count(),
        ];

        return response()->json([
            'analytics' => $analytics,
            'recommendation_stats' => $recommendationStats,
            'top_ranked_applicants' => $topRanked,
        ]);
    }
}
