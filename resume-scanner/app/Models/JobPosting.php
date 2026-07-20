<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 */
class JobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'department',
        'location',
        'employment_type',
        'seniority',
        'urgency',
        'about',
        'responsibilities',
        'requirements',
        'skills_text',
        'skills_json',
    ];

    protected $casts = [
        'skills_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(CandidateProfile::class, 'job_posting_id');
    }
}
