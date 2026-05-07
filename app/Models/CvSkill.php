<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSkill extends Model
{
    protected $fillable = [
        'cv_profile_id',
        'name',
        'category',
        'type',
        'level',
        'sort_order',
    ];

    public function cvProfile(): BelongsTo
    {
        return $this->belongsTo(CvProfile::class);
    }
}
