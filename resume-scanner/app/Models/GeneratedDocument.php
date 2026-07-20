<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use HasFactory;

    public const TYPE_APPLICATION_SLIP = 'application_slip';
    public const TYPE_INTERVIEW_LETTER = 'interview_letter';
    public const TYPE_OFFER_LETTER = 'offer_letter';

    protected $fillable = [
        'document_id',
        'type',
        'application_id',
        'interview_id',
        'applicant_user_id',
        'applicant_name',
        'job_title',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function applicantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_APPLICATION_SLIP => 'Application Slip',
            self::TYPE_INTERVIEW_LETTER => 'Interview Letter',
            self::TYPE_OFFER_LETTER => 'Offer Letter',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }
}
