<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = [
        'recruiter_id',
        'talent_id',
        'vacancy_id',
        'cv_profile_id',
        'status',
        'stage',
        'match_score',
        'applied_at',
        'last_activity_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function cvProfile(): BelongsTo
    {
        return $this->belongsTo(CvProfile::class);
    }
}
