<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Skill;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $jobs = Job::query()
            ->with(['department:id,name', 'hrOfficer:id,name'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where(function ($nested) use ($request): void {
                    $search = '%' . (string) $request->query('search') . '%';
                    $nested->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('qualifications', 'like', $search);
                });
            })
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', (int) $request->query('department_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->query('status')), fn ($query) => $query->where('status', 'published'))
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($jobs);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job->load(['department:id,name', 'hrOfficer:id,name', 'skills:id,name']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'qualifications' => ['nullable', 'string'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'min_years_experience' => ['required', 'integer', 'min:0', 'max:50'],
            'application_deadline' => ['required', 'date', 'after:now'],
            'positions' => ['required', 'integer', 'min:1', 'max:200'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:draft,published,closed'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:120'],
        ]);

        $job = Job::query()->create([
            'department_id' => $validated['department_id'] ?? null,
            'hr_officer_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'qualifications' => $validated['qualifications'] ?? null,
            'experience_level' => $validated['experience_level'] ?? null,
            'min_years_experience' => $validated['min_years_experience'],
            'application_deadline' => $validated['application_deadline'],
            'positions' => $validated['positions'],
            'location' => $validated['location'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'required_skills_json' => $validated['required_skills'] ?? [],
        ]);

        if (!empty($validated['required_skills'])) {
            $skillIds = collect($validated['required_skills'])
                ->map(function (string $skill): int {
                    $model = Skill::query()->firstOrCreate([
                        'normalized_name' => strtolower(trim($skill)),
                    ], ['name' => $skill]);

                    return (int) $model->id;
                })
                ->all();

            $job->skills()->sync($skillIds);
        }

        $this->auditLogService->log(
            $request->user()->id,
            'job.created',
            Job::class,
            $job->id,
            null,
            $job->toArray(),
            $request
        );

        return response()->json($job->load('skills:id,name'), 201);
    }

    public function update(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'qualifications' => ['nullable', 'string'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'min_years_experience' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'application_deadline' => ['sometimes', 'date'],
            'positions' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:draft,published,closed'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:120'],
        ]);

        $before = $job->toArray();

        $job->update([
            ...$validated,
            'required_skills_json' => $validated['required_skills'] ?? $job->required_skills_json,
        ]);

        if (array_key_exists('required_skills', $validated)) {
            $skillIds = collect($validated['required_skills'] ?? [])
                ->map(function (string $skill): int {
                    $model = Skill::query()->firstOrCreate([
                        'normalized_name' => strtolower(trim($skill)),
                    ], ['name' => $skill]);

                    return (int) $model->id;
                })
                ->all();

            $job->skills()->sync($skillIds);
        }

        $this->auditLogService->log(
            $request->user()->id,
            'job.updated',
            Job::class,
            $job->id,
            $before,
            $job->fresh()->toArray(),
            $request
        );

        return response()->json($job->load('skills:id,name'));
    }

    public function destroy(Request $request, Job $job): JsonResponse
    {
        $before = $job->toArray();
        $job->delete();

        $this->auditLogService->log(
            $request->user()->id,
            'job.deleted',
            Job::class,
            $before['id'] ?? null,
            $before,
            null,
            $request
        );

        return response()->json([
            'message' => 'Job deleted successfully.',
        ]);
    }
}
