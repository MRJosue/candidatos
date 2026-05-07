<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'recruiter_id',
        'company_id',
        'title',
        'department',
        'seniority',
        'employment_type',
        'work_mode',
        'location',
        'salary_min',
        'salary_max',
        'currency',
        'technical_stack',
        'description',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'technical_stack' => 'array',
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

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }
}
