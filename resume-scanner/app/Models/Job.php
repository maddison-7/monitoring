<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'hr_officer_id',
        'title',
        'description',
        'qualifications',
        'duties',
        'experience_level',
        'min_years_experience',
        'application_deadline',
        'positions',
        'location',
        'status',
        'required_skills_json',
    ];

    protected $casts = [
        'application_deadline' => 'datetime',
        'required_skills_json' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function hrOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_officer_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill')->withPivot('is_required')->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedByApplicants(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }
}
