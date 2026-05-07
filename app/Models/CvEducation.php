<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvEducation extends Model
{
    protected $table = 'cv_education';

    protected $fillable = [
        'cv_profile_id',
        'institution',
        'location',
        'degree',
        'field',
        'gpa',
        'honors',
        'thesis',
        'relevant_coursework',
        'start_date',
        'end_date',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function cvProfile(): BelongsTo
    {
        return $this->belongsTo(CvProfile::class);
    }
}
