<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    protected $fillable = [
        'recruiter_id',
        'company_id',
        'position_id',
        'title',
        'client_company',
        'location',
        'work_mode',
        'employment_type',
        'seniority',
        'status',
        'salary_min',
        'salary_max',
        'currency',
        'technical_stack',
        'description',
        'requirements',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'technical_stack' => 'array',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->position?->title ?? $this->title;
    }

    public function getDisplayCompanyAttribute(): ?string
    {
        return $this->company?->name ?? $this->client_company;
    }
}
