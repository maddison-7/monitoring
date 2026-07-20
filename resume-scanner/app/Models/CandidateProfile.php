<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $job_posting_id
 * @property string|null $resume_path
 * @property string|null $resume_original_name
 * @property string|null $raw_text
 * @property string|null $anonymized_text
 * @property array|null $parsed_json
 * @property array|null $skills_json
 * @property float $years_experience
 * @property int $match_score
 * @property string|null $recommendation
 * @property string|null $status
 */
class CandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'user_id',
        'resume_original_name',
        'resume_path',
        'raw_text',
        'anonymized_text',
        'parsed_json',
        'skills_json',
        'years_experience',
        'match_score',
        'recommendation',
        'status',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'skills_json' => 'array',
        'years_experience' => 'float',
        'match_score' => 'integer',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
