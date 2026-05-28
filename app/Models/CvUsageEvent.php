<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvUsageEvent extends Model
{
    public const TYPE_IMPORT_AI = 'import_ai';
    public const TYPE_TRANSLATION_AI = 'translation_ai';

    protected $fillable = [
        'user_id',
        'cv_usage_subscription_id',
        'cv_profile_id',
        'type',
        'quantity',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CvUsageSubscription::class, 'cv_usage_subscription_id');
    }

    public function cvProfile(): BelongsTo
    {
        return $this->belongsTo(CvProfile::class);
    }
}
