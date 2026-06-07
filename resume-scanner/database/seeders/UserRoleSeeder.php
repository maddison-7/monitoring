<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereIn('email', ['hr.officer@ajira.local', 'applicant.demo@ajira.local', 'security.recruiter@ajira.local'])
            ->delete();

        User::query()->updateOrCreate(
            ['email' => 'admin@ajira.local'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'name' => 'System Administrator',
                'role' => 'admin',
                'email_verified_at' => now(),
                'password' => Hash::make('Password@123'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'security.recruiter@ajira.local'],
            [
                'first_name' => 'Security',
                'last_name' => 'Recruiter',
                'name' => 'Security Recruiter',
                'role' => 'recruiter',
                'email_verified_at' => now(),
                'password' => Hash::make('Password@123'),
            ]
        );
    }
}
