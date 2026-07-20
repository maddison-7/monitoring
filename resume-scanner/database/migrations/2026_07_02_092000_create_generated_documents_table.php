<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('generated_documents')) {
            Schema::create('generated_documents', function (Blueprint $table) {
                $table->id();
                $table->string('document_id', 40)->unique();
                $table->string('type', 40);
                $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('interview_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('applicant_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('applicant_name');
                $table->string('job_title')->nullable();
                $table->timestamp('generated_at');
                $table->timestamps();

                $table->index(['type', 'generated_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
