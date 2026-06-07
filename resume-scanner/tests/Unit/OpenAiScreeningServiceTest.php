<?php

namespace Tests\Unit;

use App\Models\JobPosting;
use App\Services\OpenAiScreeningService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OpenAiScreeningServiceTest extends TestCase
{
    public function test_it_uses_similarity_matching_and_weighted_scoring(): void
    {
        Config::set('services.gemini.api_key', '');
        Config::set('services.scoring.weights.skills', 60);
        Config::set('services.scoring.weights.experience', 25);
        Config::set('services.scoring.weights.education', 15);

        $jobPosting = new JobPosting([
            'user_id' => 1,
            'title' => 'Frontend Engineer',
            'department' => 'Product',
            'location' => 'Remote',
            'employment_type' => 'full_time',
            'seniority' => 'mid',
            'urgency' => 'high',
            'about' => 'We need a bachelor-level engineer to build customer-facing interfaces.',
            'responsibilities' => 'Build React interfaces and integrate APIs.',
            'requirements' => 'Bachelor degree preferred. Strong React, Node.js, and PostgreSQL experience.',
            'skills_text' => 'React, Node.js, PostgreSQL',
            'skills_json' => ['React', 'Node.js', 'PostgreSQL'],
        ]);

        $service = new OpenAiScreeningService();
        $result = $service->scoreCandidate($jobPosting, [
            'skills' => ['React.js', 'NodeJS', 'Postgres'],
            'years_of_experience' => 6,
            'summary' => 'Bachelor of Science in Computer Science with React.js and NodeJS delivery experience.',
        ], 'React.js engineer with NodeJS project delivery and a bachelor of science in computer science.');

        $this->assertGreaterThanOrEqual(70, $result['match_score']);
        $this->assertContains('react', $result['matched_skills']);
        $this->assertContains('node.js', $result['matched_skills']);
        $this->assertContains('postgresql', $result['matched_skills']);
        $this->assertSame([], $result['missing_skills']);
        $this->assertContains($result['recommendation'], ['Shortlisted', 'Review']);
        $this->assertArrayHasKey('score_breakdown', $result);
        $this->assertArrayHasKey('hard_constraint', $result['score_breakdown']);
        $this->assertTrue((bool) $result['score_breakdown']['hard_constraint']['passed']);
        $this->assertSame(60, $result['score_breakdown']['weights']['scoring_skills_weight']);
    }

    public function test_it_caps_score_when_must_have_constraints_fail(): void
    {
        Config::set('services.gemini.api_key', '');

        $jobPosting = new JobPosting([
            'user_id' => 1,
            'title' => 'Backend Engineer',
            'department' => 'Engineering',
            'seniority' => 'mid',
            'about' => 'Backend role for API platform.',
            'responsibilities' => 'Build APIs and services.',
            'requirements' => 'Must have Laravel and PostgreSQL experience. Required API design skills.',
            'skills_text' => 'Laravel, PostgreSQL, API',
            'skills_json' => ['Laravel', 'PostgreSQL', 'API'],
        ]);

        $service = new OpenAiScreeningService();
        $result = $service->scoreCandidate($jobPosting, [
            'skills' => ['React', 'Figma'],
            'years_of_experience' => 7,
            'summary' => 'Strong UI engineer without backend stack experience.',
        ], 'Frontend-focused candidate with React and Figma projects.');

        $this->assertLessThanOrEqual(55, $result['match_score']);
        $this->assertFalse((bool) $result['score_breakdown']['hard_constraint']['passed']);
        $this->assertNotEmpty($result['score_breakdown']['hard_constraint']['missing_must_have']);
    }

    public function test_it_considers_gpa_when_matching_education(): void
    {
        Config::set('services.gemini.api_key', '');

        $jobPosting = new JobPosting([
            'user_id' => 1,
            'title' => 'Associate Analyst',
            'department' => 'Engineering',
            'seniority' => 'junior',
            'about' => 'Entry level role supporting reporting and data review.',
            'responsibilities' => 'Review data and prepare reports.',
            'requirements' => 'Bachelor degree required. Minimum GPA 3.5. Strong Excel and reporting skills.',
            'skills_text' => 'Excel, report writing',
            'skills_json' => ['Excel', 'report writing'],
        ]);

        $service = new OpenAiScreeningService();
        $result = $service->scoreCandidate($jobPosting, [
            'skills' => ['Excel', 'report writing'],
            'years_of_experience' => 2,
            'summary' => 'Bachelor of Science in Business Administration, GPA 3.8/4.0, experience with reporting and Excel.',
        ], 'Bachelor of Science in Business Administration, GPA 3.8/4.0, reporting and Excel experience.');

        $this->assertGreaterThanOrEqual(70, $result['match_score']);
        $this->assertSame(3.5, $result['score_breakdown']['gpa_requirement']);
        $this->assertSame(3.8, $result['score_breakdown']['candidate_gpa']);
        $this->assertGreaterThanOrEqual(90, (int) $result['score_breakdown']['gpa_score']);
    }

    public function test_it_heuristically_extracts_experience_and_common_skills_from_resume_text(): void
    {
        Config::set('services.gemini.api_key', '');

        $service = new OpenAiScreeningService();
        $result = $service->parseResume(
            'Customer service representative Jan 2020 - Present. Strong communication, Microsoft Office, and data entry skills.'
        );

        $this->assertGreaterThanOrEqual(5.0, $result['years_of_experience']);
        $this->assertContains('customer service', $result['skills']);
        $this->assertContains('communication', $result['skills']);
        $this->assertContains('microsoft office', $result['skills']);
        $this->assertContains('data entry', $result['skills']);
    }
}