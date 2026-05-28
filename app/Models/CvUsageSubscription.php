<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvUsageSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'cv_usage_plan_id',
        'current_period_starts_at',
        'current_period_ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CvUsagePlan::class, 'cv_usage_plan_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CvUsageEvent::class);
    }
}
