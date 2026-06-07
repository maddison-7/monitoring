<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('applications', 'offer_sent_at')) {
                $table->timestamp('offer_sent_at')->nullable()->after('applied_at');
            }

            if (!Schema::hasColumn('applications', 'offer_status')) {
                $table->string('offer_status', 30)->nullable()->after('offer_sent_at');
            }

            if (!Schema::hasColumn('applications', 'offer_response_at')) {
                $table->timestamp('offer_response_at')->nullable()->after('offer_status');
            }

            if (!Schema::hasColumn('applications', 'offer_message')) {
                $table->text('offer_message')->nullable()->after('offer_response_at');
            }

            if (!Schema::hasColumn('applications', 'onboarding_status')) {
                $table->string('onboarding_status', 30)->nullable()->after('offer_message');
            }

            if (!Schema::hasColumn('applications', 'onboarding_started_at')) {
                $table->timestamp('onboarding_started_at')->nullable()->after('onboarding_status');
            }

            if (!Schema::hasColumn('applications', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_started_at');
            }

            if (!Schema::hasColumn('applications', 'onboarding_notes')) {
                $table->text('onboarding_notes')->nullable()->after('onboarding_completed_at');
            }

            if (!Schema::hasColumn('applications', 'placement_status')) {
                $table->string('placement_status', 30)->nullable()->after('onboarding_notes');
            }

            if (!Schema::hasColumn('applications', 'placement_closed_at')) {
                $table->timestamp('placement_closed_at')->nullable()->after('placement_status');
            }

            if (!Schema::hasColumn('applications', 'placement_notes')) {
                $table->text('placement_notes')->nullable()->after('placement_closed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table): void {
            $columns = [
                'offer_sent_at',
                'offer_status',
                'offer_response_at',
                'offer_message',
                'onboarding_status',
                'onboarding_started_at',
                'onboarding_completed_at',
                'onboarding_notes',
                'placement_status',
                'placement_closed_at',
                'placement_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
