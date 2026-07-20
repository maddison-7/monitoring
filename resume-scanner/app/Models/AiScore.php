<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'match_percentage',
        'fit_score',
        'recommendation_level',
        'matched_skills',
        'missing_skills',
        'summary',
        'model_name',
        'skills_score',
        'experience_score',
        'education_score',
        'gpa_score',
        'strengths',
        'weaknesses',
        'risk_factors',
        'hiring_advantages',
        'explanation',
    ];

    protected $casts = [
        'matched_skills' => 'array',
        'missing_skills' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'risk_factors' => 'array',
        'hiring_advantages' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
