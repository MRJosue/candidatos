<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvProfile extends Model
{
    public const MAX_PER_TALENT = 2;

    public const SIDE_SECTIONS = ['software', 'skills', 'languages', 'soft_skills'];

    public const MAIN_SECTIONS = ['experiences', 'education'];

    protected $fillable = [
        'user_id',
        'talent_id',
        'cv_template_id',
        'language',
        'source_cv_profile_id',
        'title',
        'full_name',
        'email',
        'phone',
        'location',
        'headline',
        'tagline',
        'summary',
        'objective',
        'skills_section_title',
        'soft_skills_section_title',
        'section_order',
        'awards',
        'leadership_activities',
        'interests',
        'linkedin_url',
        'portfolio_url',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'section_order' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CvTemplate::class, 'cv_template_id');
    }

    public function sourceCvProfile(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_cv_profile_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'source_cv_profile_id')->latest();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CvExperience::class)->orderBy('sort_order')->latest('start_date');
    }

    public function education(): HasMany
    {
        return $this->hasMany(CvEducation::class)->orderBy('sort_order')->latest('end_date');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CvSkill::class)->orderBy('sort_order')->orderBy('name');
    }

    public static function defaultSectionOrder(): array
    {
        return [
            'side' => self::SIDE_SECTIONS,
            'main' => self::MAIN_SECTIONS,
        ];
    }

    public static function normalizeSectionOrder(array $sections, array $allowed): array
    {
        return collect($sections)
            ->filter(fn ($section) => is_string($section) && in_array($section, $allowed, true))
            ->unique()
            ->merge(collect($allowed)->diff($sections))
            ->values()
            ->all();
    }

    public function normalizedSectionOrder(): array
    {
        $sectionOrder = is_array($this->section_order) ? $this->section_order : [];

        return [
            'side' => self::normalizeSectionOrder($sectionOrder['side'] ?? self::SIDE_SECTIONS, self::SIDE_SECTIONS),
            'main' => self::normalizeSectionOrder($sectionOrder['main'] ?? self::MAIN_SECTIONS, self::MAIN_SECTIONS),
        ];
    }

    public static function languageOptions(): array
    {
        return [
            'es' => 'Español',
            'en' => 'Inglés',
        ];
    }

    public function languageLabel(): string
    {
        return self::languageOptions()[$this->language ?: 'es'] ?? strtoupper((string) $this->language);
    }
}
