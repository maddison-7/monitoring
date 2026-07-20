<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    use HasFactory;

    protected $table = 'applicant_achievements';

    protected $fillable = [
        'applicant_id',
        'title',
        'description',
        'achieved_on',
    ];

    protected $casts = [
        'achieved_on' => 'date',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
