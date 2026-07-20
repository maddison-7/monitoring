<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'gpa')) {
                $table->decimal('gpa', 3, 2)->nullable()->after('bio');
            }
        });

        if (!Schema::hasTable('applicant_projects')) {
            Schema::create('applicant_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('technologies')->nullable();
                $table->string('url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('applicant_achievements')) {
            Schema::create('applicant_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('achieved_on')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_achievements');
        Schema::dropIfExists('applicant_projects');

        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'gpa')) {
                $table->dropColumn('gpa');
            }
        });
    }
};
