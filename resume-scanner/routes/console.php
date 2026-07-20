<?php

use App\Models\Job;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('governance:close-expired-jobs', function () {
    $closed = Job::query()
        ->publishedExpired()
        ->update(['status' => 'closed']);

    $this->info('Expired published jobs closed: ' . $closed . '.');
})->purpose('Close published job posts that have passed their application deadline.');

Artisan::command('governance:purge-closed-jobs', function () {
    $deleted = 0;

    Job::purgeableClosed()
        ->select(['id'])
        ->chunkById(100, function ($jobs) use (&$deleted): void {
            foreach ($jobs as $job) {
                Job::query()->whereKey((int) $job->id)->delete();
                $deleted++;
            }
        });

    $this->info('Closed jobs purged: ' . $deleted . '.');
})->purpose('Permanently delete closed jobs that no longer have active applications.');

app()->afterResolving(Schedule::class, function (Schedule $schedule): void {
    $schedule->command('governance:close-expired-jobs')->dailyAt('01:00');
    $schedule->command('governance:purge-closed-jobs')->dailyAt('01:10');
});
