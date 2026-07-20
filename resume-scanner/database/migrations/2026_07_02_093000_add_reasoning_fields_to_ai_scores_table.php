<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_scores', 'skills_score')) {
                $table->decimal('skills_score', 5, 2)->nullable()->after('match_percentage');
            }
            if (!Schema::hasColumn('ai_scores', 'experience_score')) {
                $table->decimal('experience_score', 5, 2)->nullable()->after('skills_score');
            }
            if (!Schema::hasColumn('ai_scores', 'education_score')) {
                $table->decimal('education_score', 5, 2)->nullable()->after('experience_score');
            }
            if (!Schema::hasColumn('ai_scores', 'gpa_score')) {
                $table->decimal('gpa_score', 5, 2)->nullable()->after('education_score');
            }
            if (!Schema::hasColumn('ai_scores', 'strengths')) {
                $table->json('strengths')->nullable()->after('missing_skills');
            }
            if (!Schema::hasColumn('ai_scores', 'weaknesses')) {
                $table->json('weaknesses')->nullable()->after('strengths');
            }
            if (!Schema::hasColumn('ai_scores', 'risk_factors')) {
                $table->json('risk_factors')->nullable()->after('weaknesses');
            }
            if (!Schema::hasColumn('ai_scores', 'hiring_advantages')) {
                $table->json('hiring_advantages')->nullable()->after('risk_factors');
            }
            if (!Schema::hasColumn('ai_scores', 'explanation')) {
                $table->text('explanation')->nullable()->after('summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_scores', function (Blueprint $table) {
            $columns = ['skills_score', 'experience_score', 'education_score', 'gpa_score', 'strengths', 'weaknesses', 'risk_factors', 'hiring_advantages', 'explanation'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('ai_scores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
