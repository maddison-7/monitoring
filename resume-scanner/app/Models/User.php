<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Interview;
use App\Models\Job;
use App\Models\LoginHistory;
use App\Models\Report;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'role',
        'profile_picture',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function candidateProfiles(): HasMany
    {
        return $this->hasMany(CandidateProfile::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'hr_officer_id');
    }

    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class);
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(
            Application::class,
            Applicant::class,
            'user_id',
            'applicant_id',
            'id',
            'id'
        );
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'interviewer_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function getAvatarUrl(): ?string
    {
        $path = trim((string) ($this->profile_picture ?? ''));
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:image/'])) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $baseUrl = Str::startsWith($normalized, 'storage/')
            ? '/' . $normalized
            : '/storage/' . $normalized;

        $version = $this->updated_at?->timestamp;

        return $version ? ($baseUrl . '?v=' . $version) : $baseUrl;
    }

    public function getInitials(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1, 'UTF-8');
        $last = mb_substr($this->last_name ?? '', 0, 1, 'UTF-8');

        $initials = strtoupper($first . $last);
        if ($initials !== '') {
            return $initials;
        }

        $nameInitials = collect(preg_split('/\s+/', trim((string) ($this->name ?? ''))) ?: [])
            ->filter(fn (string $segment): bool => $segment !== '')
            ->take(2)
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1, 'UTF-8'), 'UTF-8'))
            ->implode('');

        return $nameInitials !== '' ? $nameInitials : 'U';
    }
}
