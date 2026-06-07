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
        'recommendation_level',
        'matched_skills',
        'missing_skills',
        'summary',
        'model_name',
    ];

    protected $casts = [
        'matched_skills' => 'array',
        'missing_skills' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
