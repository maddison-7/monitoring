<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_scores', 'fit_score')) {
                $table->decimal('fit_score', 15, 4)->nullable()->after('match_percentage');
            }
        });

        Schema::table('ai_scores', function (Blueprint $table) {
            $table->index(['match_percentage', 'fit_score'], 'ai_scores_match_fit_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_scores', function (Blueprint $table) {
            $table->dropIndex('ai_scores_match_fit_idx');
            if (Schema::hasColumn('ai_scores', 'fit_score')) {
                $table->dropColumn('fit_score');
            }
        });
    }
};
