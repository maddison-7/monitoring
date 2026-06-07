<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'Project Management',
            'Monitoring and Evaluation',
            'Data Analysis',
            'Public Administration',
            'Policy Development',
            'Procurement',
            'Financial Reporting',
            'Payroll Processing',
            'Human Resource Management',
            'Recruitment',
            'Communication',
            'Leadership',
            'Microsoft Excel',
            'Power BI',
            'SQL',
            'Laravel',
            'PHP',
            'JavaScript',
            'API Design',
            'Cybersecurity',
        ];

        foreach ($skills as $skill) {
            Skill::query()->updateOrCreate(
                ['normalized_name' => strtolower($skill)],
                ['name' => $skill],
            );
        }
    }
}
