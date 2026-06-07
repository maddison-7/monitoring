<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'job_id',
        'applicant_id',
        'cover_letter_text',
        'status',
        'duplicate_hash',
        'applied_at',
        'offer_sent_at',
        'offer_status',
        'offer_response_at',
        'offer_message',
        'onboarding_status',
        'onboarding_started_at',
        'onboarding_completed_at',
        'onboarding_notes',
        'placement_status',
        'placement_closed_at',
        'placement_notes',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'offer_sent_at' => 'datetime',
        'offer_response_at' => 'datetime',
        'onboarding_started_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'placement_closed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function aiScore(): HasOne
    {
        return $this->hasOne(AiScore::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }
}
