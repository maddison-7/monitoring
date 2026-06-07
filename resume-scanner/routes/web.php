<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\RolePanelController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('/panel')->name('panel.')->group(function () {
        Route::get('/admin', [RolePanelController::class, 'admin'])
            ->middleware('role:admin')
            ->name('admin');

        Route::redirect('/applicant', '/applicant/profile')
            ->middleware('role:applicant')
            ->name('applicant');

        Route::get('/recruiter', [RolePanelController::class, 'recruiter'])
            ->middleware('role:hr_officer,recruiter')
            ->name('recruiter');
    });

    Route::middleware('role:admin,hr_officer')->group(function () {
        Route::get('/upload-cv', fn () => redirect()->route('hr.candidate.ranking'))
            ->name('cv.upload.form');

        Route::post('/upload-cv', fn () => redirect()->route('hr.candidate.ranking'))
            ->name('cv.upload');

        Route::post('/upload-cv/async', function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Legacy upload endpoint is disabled. Use /hr/candidate-ranking in the unified recruitment flow.',
                ], 410);
            }

            return redirect()->route('hr.candidate.ranking');
        })->name('cv.upload.async');

        Route::get('/job-description', fn () => redirect()->route('hr.jobs.index'))
            ->name('job.description');

        Route::post('/job-description', fn () => redirect()->route('hr.jobs.index'))
            ->name('job.description.store');

        Route::put('/job-description/{jobPosting}', fn () => redirect()->route('hr.jobs.index'))
            ->name('job.description.update');

        Route::delete('/job-description/{jobPosting}', fn () => redirect()->route('hr.jobs.index'))
            ->name('job.description.delete');
    });


    Route::middleware('role:applicant')->prefix('/applicant')->name('applicant.')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'applicantDashboard'])->name('dashboard');
        Route::get('/dashboard/live', [PortalController::class, 'applicantDashboardLive'])->name('dashboard.live');
        Route::get('/applications', [PortalController::class, 'applicantApplications'])->name('applications');
        Route::get('/recommendations', [PortalController::class, 'applicantRecommendations'])->name('recommendations');
        Route::get('/interviews', [PortalController::class, 'applicantInterviews'])->name('interviews');
        Route::post('/interviews/{interview}/respond', [PortalController::class, 'applicantRespondInterview'])->name('interviews.respond');
        Route::get('/notifications', [PortalController::class, 'applicantNotifications'])->name('notifications');
        Route::get('/downloads', [PortalController::class, 'applicantDownloads'])->name('downloads');
        Route::get('/profile', [PortalController::class, 'applicantProfile'])->name('profile');
        Route::put('/profile', [PortalController::class, 'updateApplicantProfile'])->name('profile.update');
        Route::get('/jobs', [PortalController::class, 'applicantJobs'])->name('jobs');
        Route::get('/jobs/department/{department}', [PortalController::class, 'applicantDepartmentJobs'])->name('jobs.department');
        Route::get('/jobs/{job}', [PortalController::class, 'applicantJobShow'])->name('jobs.show');
        Route::get('/jobs/{job}/apply', [PortalController::class, 'applicantApplyForm'])->name('jobs.apply');
        Route::post('/jobs/{job}/apply', [PortalController::class, 'applicantApplySubmit'])->name('jobs.apply.submit');
        Route::post('/chatbot/upload-cv', [PortalController::class, 'applicantChatbotUploadCv'])->name('chatbot.upload-cv');
        Route::post('/jobs/{job}/save', [PortalController::class, 'toggleSavedJob'])->name('jobs.save');
        Route::post('/applications/{application}/offer/respond', [PortalController::class, 'applicantRespondOffer'])->name('applications.offer.respond');
        Route::get('/downloads/application-slip/{application}', [PortalController::class, 'downloadApplicationSlip'])->name('downloads.application-slip');
        Route::get('/downloads/interview-invitation/{interview}', [PortalController::class, 'downloadInterviewInvitation'])->name('downloads.interview-invitation');
        Route::get('/downloads/offer-letter/{application}', [PortalController::class, 'downloadOfferLetter'])->name('downloads.offer-letter');
    });

    Route::middleware('role:admin,hr_officer,recruiter')->prefix('/hr')->name('hr.')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'hrDashboard'])->name('dashboard');
        Route::get('/dashboard/live', [PortalController::class, 'hrDashboardLive'])->name('dashboard.live');
        Route::get('/interviews', [PortalController::class, 'hrInterviews'])->name('interviews');
        Route::get('/analytics-reports', [PortalController::class, 'hrAnalyticsReports'])->name('analytics.reports');
        Route::get('/notifications', [PortalController::class, 'hrNotifications'])->name('notifications');
        Route::get('/departments', [PortalController::class, 'hrDepartments'])->name('departments');
        Route::get('/jobs', [PortalController::class, 'hrJobs'])->name('jobs.index');
        Route::get('/jobs/create', [PortalController::class, 'hrJobsCreate'])->name('jobs.create');
        Route::post('/jobs', [PortalController::class, 'hrJobsStore'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [PortalController::class, 'hrJobsEdit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [PortalController::class, 'hrJobsUpdate'])->name('jobs.update');
        Route::delete('/jobs/{job}', [PortalController::class, 'hrJobsDelete'])->name('jobs.delete');
        Route::get('/candidate-ranking', [PortalController::class, 'hrCandidateRanking'])->name('candidate.ranking');
        Route::get('/applications/{application}/cv', [PortalController::class, 'hrApplicationCv'])->name('applications.cv');
        Route::post('/applications/{application}/status', [PortalController::class, 'hrUpdateApplicationStatus'])->name('applications.status');
        Route::post('/applications/{application}/shortlist', [PortalController::class, 'hrShortlistCandidate'])->name('applications.shortlist');
        Route::post('/applications/{application}/interviews', [PortalController::class, 'hrScheduleInterview'])->name('applications.interviews.schedule');
        Route::post('/applications/{application}/offer', [PortalController::class, 'hrSendOffer'])->name('applications.offer.send');
        Route::post('/applications/{application}/onboarding', [PortalController::class, 'hrUpdateOnboarding'])->name('applications.onboarding.update');
        Route::post('/applications/{application}/placement/close', [PortalController::class, 'hrClosePlacement'])->name('applications.placement.close');
        Route::post('/interviews/{interview}/result', [PortalController::class, 'hrUpdateInterviewResult'])->name('interviews.result');
        Route::get('/reports/export/csv', [PortalController::class, 'hrExportCsv'])->name('reports.export.csv');
        Route::get('/reports/export/excel', [PortalController::class, 'hrExportExcel'])->name('reports.export.excel');
        Route::get('/reports/export/pdf', [PortalController::class, 'hrExportPdf'])->name('reports.export.pdf');
    });

    Route::middleware('role:admin')->prefix('/admin')->name('admin.')->group(function () {
        Route::get('/recruiters', [AdminController::class, 'recruiters'])->name('recruiters');
        Route::post('/recruiters', [AdminController::class, 'storeRecruiter'])->name('recruiters.store');
        Route::put('/recruiters/{recruiter}', [AdminController::class, 'updateRecruiter'])->name('recruiters.update');
        Route::put('/recruiters/{recruiter}/password', [AdminController::class, 'resetRecruiterPassword'])->name('recruiters.password');
        Route::delete('/recruiters/{recruiter}', [AdminController::class, 'deleteRecruiter'])->name('recruiters.delete');
        Route::post('/applicants', [AdminController::class, 'storeApplicant'])->name('applicants.store');
        Route::put('/applicants/{applicant}', [AdminController::class, 'updateApplicant'])->name('applicants.update');
        Route::put('/applicants/{applicant}/password', [AdminController::class, 'resetApplicantPassword'])->name('applicants.password');
        Route::delete('/applicants/{applicant}', [AdminController::class, 'deleteApplicant'])->name('applicants.delete');
        Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonateUser'])->name('users.impersonate');
        Route::get('/governance', [AdminController::class, 'governance'])->name('governance');
        Route::post('/governance/sync-ai-statuses', [AdminController::class, 'syncAiStatuses'])->name('governance.sync-ai-statuses');
        Route::post('/governance/close-expired-jobs', [AdminController::class, 'closeExpiredJobs'])->name('governance.close-expired-jobs');
        Route::post('/governance/purge-closed-jobs', [AdminController::class, 'purgeClosedJobs'])->name('governance.purge-closed-jobs');
        Route::post('/governance/close-stale-interviews', [AdminController::class, 'closeStaleInterviews'])->name('governance.close-stale-interviews');
        Route::get('/jobs', [AdminController::class, 'jobs'])->name('jobs');
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/reports/export/csv', [AdminController::class, 'exportReportsCsv'])->name('reports.export.csv');
        Route::get('/reports/export/excel', [AdminController::class, 'exportReportsExcel'])->name('reports.export.excel');
        Route::get('/reports/export/pdf', [AdminController::class, 'exportReportsPdf'])->name('reports.export.pdf');
        Route::get('/audit-logs', [AdminController::class, 'audit'])->name('audit');
        Route::get('/audit-logs/export/csv', [AdminController::class, 'exportAuditCsv'])->name('audit.export.csv');
        Route::get('/audit-logs/export/pdf', [AdminController::class, 'exportAuditPdf'])->name('audit.export.pdf');
        Route::get('/api-usage', [AdminController::class, 'apiUsage'])->name('api');
        Route::get('/system-settings', [AdminController::class, 'system'])->name('system');
        Route::post('/system-settings', [AdminController::class, 'updateSystem'])->name('system.update');
    });

    Route::middleware('role:admin,hr_officer,recruiter,applicant')->group(function () {
        Route::get('/settings', [WorkspaceController::class, 'settings'])->name('settings');
        Route::post('/settings', [WorkspaceController::class, 'updateSettings'])->name('settings.update');
        Route::post('/impersonation/leave', [AdminController::class, 'leaveImpersonation'])->name('admin.impersonation.leave');
        Route::view('/ajira-dashboard', 'ajira-dashboard')
            ->middleware('role:admin,hr_officer,recruiter,applicant')
            ->name('ajira.dashboard');
    });
});

Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');
