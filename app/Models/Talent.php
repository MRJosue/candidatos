<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Talent extends Model
{
    protected $table = 'talents';

    protected $fillable = [
        'recruiter_id',
        'user_id',
        'public_token',
        'first_name',
        'last_name',
        'email',
        'phone',
        'location',
        'headline',
        'target_position',
        'seniority',
        'source',
        'status',
        'availability',
        'salary_expectation_min',
        'salary_expectation_max',
        'currency',
        'technical_stack',
        'languages',
        'links',
        'technical_summary',
        'notes',
        'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'technical_stack' => 'array',
            'languages' => 'array',
            'links' => 'array',
            'last_contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Talent $talent): void {
            $talent->public_token ??= (string) Str::uuid();
        });
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cvProfile(): HasOne
    {
        return $this->hasOne(CvProfile::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
