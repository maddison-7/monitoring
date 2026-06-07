<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL DEFAULT 'hr_officer'");
            DB::statement("UPDATE users SET role='hr_officer' WHERE role='recruiter'");
            DB::statement("UPDATE users SET role='admin' WHERE role IN ('hr_manager','hr-manager')");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE users SET role='recruiter' WHERE role='hr_officer'");
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','recruiter','hr_manager') NOT NULL DEFAULT 'recruiter'");
        }
    }
};
