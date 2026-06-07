<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jobs') || Schema::hasColumn('jobs', 'duties')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table): void {
            $table->text('duties')->nullable()->after('qualifications');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('jobs') || !Schema::hasColumn('jobs', 'duties')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropColumn('duties');
        });
    }
};
