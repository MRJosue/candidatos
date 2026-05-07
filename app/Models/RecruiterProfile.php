<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterProfile extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'company_name',
        'phone',
        'website_url',
        'bio',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
