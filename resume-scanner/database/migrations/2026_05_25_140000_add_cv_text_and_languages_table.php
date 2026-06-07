<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table): void {
            if (!Schema::hasColumn('applicants', 'cv_text')) {
                $table->longText('cv_text')->nullable()->after('cv_hash');
            }

            if (!Schema::hasColumn('applicants', 'cv_fingerprint')) {
                $table->text('cv_fingerprint')->nullable()->after('cv_text');
            }
        });

        if (!Schema::hasTable('applicant_languages')) {
            Schema::create('applicant_languages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
                $table->string('language', 80);
                $table->string('proficiency', 40)->nullable();
                $table->timestamps();

                $table->unique(['applicant_id', 'language']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicant_languages')) {
            Schema::dropIfExists('applicant_languages');
        }

        Schema::table('applicants', function (Blueprint $table): void {
            if (Schema::hasColumn('applicants', 'cv_fingerprint')) {
                $table->dropColumn('cv_fingerprint');
            }

            if (Schema::hasColumn('applicants', 'cv_text')) {
                $table->dropColumn('cv_text');
            }
        });
    }
};
