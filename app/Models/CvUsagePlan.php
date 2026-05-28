<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvUsagePlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_quota',
        'price_before_tax_cents',
        'price_with_tax_cents',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_quota' => 'integer',
            'price_before_tax_cents' => 'integer',
            'price_with_tax_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CvUsageSubscription::class);
    }
}
