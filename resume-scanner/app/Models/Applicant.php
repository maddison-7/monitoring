<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'date_of_birth',
        'gender',
        'national_id',
        'address',
        'city',
        'country',
        'bio',
        'languages_json',
        'cv_path',
        'cv_hash',
        'cv_text',
        'cv_fingerprint',
        'cover_letter_path',
        'profile_completed_at',
        'last_login_at',
    ];

    protected $casts = [
        'languages_json' => 'array',
        'date_of_birth' => 'date',
        'profile_completed_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'applicant_skill')->withPivot('proficiency')->withTimestamps();
    }

    public function languages(): HasMany
    {
        return $this->hasMany(ApplicantLanguage::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }
}
