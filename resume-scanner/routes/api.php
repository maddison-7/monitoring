<?php

use App\Http\Controllers\Api\ApplicantProfileController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HrDashboardController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShortlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register-applicant', [AuthController::class, 'registerApplicant']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::middleware('api.role:applicant')->prefix('/applicant')->group(function () {
            Route::get('/profile', [ApplicantProfileController::class, 'show']);
            Route::put('/profile', [ApplicantProfileController::class, 'update']);
            Route::post('/profile/education', [ApplicantProfileController::class, 'addEducation']);
            Route::post('/profile/experience', [ApplicantProfileController::class, 'addExperience']);
            Route::post('/profile/certifications', [ApplicantProfileController::class, 'addCertification']);
            Route::post('/profile/skills', [ApplicantProfileController::class, 'syncSkills']);
            Route::post('/profile/documents', [ApplicantProfileController::class, 'uploadDocuments']);

            Route::post('/jobs/{job}/apply', [ApplicationController::class, 'apply']);
            Route::get('/applications', [ApplicationController::class, 'myApplications']);
        });

        Route::middleware('api.role:hr_officer,admin')->group(function () {
            Route::post('/jobs', [JobController::class, 'store']);
            Route::put('/jobs/{job}', [JobController::class, 'update']);
            Route::delete('/jobs/{job}', [JobController::class, 'destroy']);

            Route::get('/hr/dashboard', [HrDashboardController::class, 'summary']);
            Route::get('/hr/applications', [ApplicationController::class, 'listForHr']);
            Route::patch('/hr/applications/{application}/status', [ApplicationController::class, 'updateStatus']);

            Route::post('/hr/jobs/{job}/shortlist', [ShortlistController::class, 'shortlistTopCandidates']);

            Route::post('/hr/applications/{application}/interviews', [InterviewController::class, 'schedule']);
            Route::patch('/hr/interviews/{interview}/result', [InterviewController::class, 'updateResult']);
        });

        Route::middleware('api.role:admin,hr_officer')->group(function () {
            Route::get('/reports/recruitment-summary', [ReportController::class, 'recruitmentSummary']);
        });
    });
});
