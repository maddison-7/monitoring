<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('seniority')->nullable();
            $table->string('salary')->nullable();
            $table->string('urgency')->nullable();
            $table->text('about');
            $table->text('responsibilities');
            $table->text('requirements');
            $table->text('skills_text')->nullable();
            $table->json('skills_json')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resume_original_name');
            $table->string('resume_path');
            $table->longText('raw_text')->nullable();
            $table->longText('anonymized_text')->nullable();
            $table->json('parsed_json')->nullable();
            $table->json('skills_json')->nullable();
            $table->decimal('years_experience', 5, 2)->nullable();
            $table->unsignedTinyInteger('match_score')->nullable();
            $table->string('recommendation')->nullable();
            $table->string('status')->default('processed');
            $table->timestamps();

            $table->index(['job_posting_id', 'match_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
        Schema::dropIfExists('job_postings');
    }
};
