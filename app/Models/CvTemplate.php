<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'preview_image_path',
        'is_premium',
        'price_cents',
        'currency',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function cvProfiles(): HasMany
    {
        return $this->hasMany(CvProfile::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
