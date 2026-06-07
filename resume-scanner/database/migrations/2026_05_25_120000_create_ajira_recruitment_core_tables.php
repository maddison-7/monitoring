<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'payload') && !Schema::hasTable('queue_jobs')) {
            Schema::rename('jobs', 'queue_jobs');
        }

        $createIfMissing = function (string $tableName, callable $callback): void {
            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, $callback);
            }
        };

        $createIfMissing('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 32)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $createIfMissing('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('national_id', 64)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->text('bio')->nullable();
            $table->json('languages_json')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_hash', 64)->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['gender', 'city']);
        });

        $createIfMissing('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        $createIfMissing('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hr_officer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('qualifications')->nullable();
            $table->string('experience_level', 60)->nullable();
            $table->unsignedSmallInteger('min_years_experience')->default(0);
            $table->dateTime('application_deadline');
            $table->unsignedInteger('positions')->default(1);
            $table->string('location')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('required_skills_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'application_deadline']);
            $table->index(['department_id', 'created_at']);
        });

        $createIfMissing('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 40)->unique();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->text('cover_letter_text')->nullable();
            $table->string('status', 40)->default('submitted');
            $table->string('duplicate_hash', 64)->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['job_id', 'applicant_id']);
            $table->index(['job_id', 'status']);
        });

        $createIfMissing('ai_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('match_percentage', 5, 2);
            $table->string('recommendation_level', 40);
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->text('summary')->nullable();
            $table->string('model_name', 120)->nullable();
            $table->timestamps();

            $table->index(['match_percentage', 'recommendation_level']);
        });

        $createIfMissing('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('mode', 20)->default('online');
            $table->string('meeting_link')->nullable();
            $table->string('venue')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
        });

        $createIfMissing('education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('institution');
            $table->string('level', 100);
            $table->string('field_of_study', 150)->nullable();
            $table->string('grade')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        $createIfMissing('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('certificate_number')->nullable();
            $table->timestamps();
        });

        $createIfMissing('work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('job_title');
            $table->string('company');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $createIfMissing('applicant_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('proficiency')->nullable();
            $table->timestamps();

            $table->unique(['applicant_id', 'skill_id']);
        });

        $createIfMissing('job_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['job_id', 'skill_id']);
        });

        $createIfMissing('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 120);
            $table->json('payload');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['type', 'generated_at']);
        });

        $createIfMissing('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });

        $createIfMissing('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('job_skill');
        Schema::dropIfExists('applicant_skill');
        Schema::dropIfExists('work_experiences');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('education');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('ai_scores');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('departments');
    }
};
