<?php

namespace App\Console\Commands;

use App\Jobs\ProcessApplicationAiScore;
use App\Models\Application;
use Illuminate\Console\Command;

class RescoreApplications extends Command
{
    /**
     * Mirrors PortalController::FINAL_DECISION_STATUSES — applications already at a
     * final decision are not worth re-scoring.
     */
    private const FINAL_DECISION_STATUSES = ['hired', 'placed', 'offer_declined', 'rejected'];

    protected $signature = 'ai:rescore-applications
        {--job= : Only rescore applications for this job ID}
        {--status=* : Only rescore applications currently in these statuses}
        {--chunk=200 : Chunk size for iterating applications}
        {--dry-run : Report how many applications would be rescored without dispatching}';

    protected $description = 'Recalculate AI match/fit scores for applications that already have an ai_scores row, using the current scoring formula.';

    public function handle(): int
    {
        $query = Application::query()
            ->whereHas('aiScore')
            ->whereNotIn('status', self::FINAL_DECISION_STATUSES);

        if ($jobId = $this->option('job')) {
            $query->where('job_id', (int) $jobId);
        }

        $statuses = (array) $this->option('status');
        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Would rescore {$count} application(s).");

            return self::SUCCESS;
        }

        $dispatched = 0;
        $query->select(['id'])->chunkById((int) $this->option('chunk'), function ($applications) use (&$dispatched): void {
            foreach ($applications as $application) {
                ProcessApplicationAiScore::dispatch((int) $application->id, true);
                $dispatched++;
            }
        });

        $this->info("Dispatched rescoring for {$dispatched} application(s).");

        return self::SUCCESS;
    }
}
