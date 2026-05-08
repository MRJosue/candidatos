<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    public const DEFAULT_STAGE = 'waiting_feedback';

    public const STAGE_OPTIONS = [
        'waiting_feedback' => 'En espera retroalimentación',
        'socioeconomic_study' => 'Estudio socioeconómico',
        'psychometric_tests' => 'Pruebas psicométricas',
        'technical_interview' => 'Entrevista técnica',
        'review' => 'En revisión',
        'hr_interview' => 'Entrevista RH',
        'offer_sent' => 'Oferta enviada',
        'hired' => 'Contratado',
        'rejected' => 'Rechazado',
    ];

    public const LEGACY_STAGE_MAP = [
        'screening' => 'review',
        'interview' => 'technical_interview',
        'technical_test' => 'psychometric_tests',
        'offer' => 'offer_sent',
    ];

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

    public static function stageOptions(): array
    {
        return self::STAGE_OPTIONS;
    }

    public static function normalizedStage(?string $stage): ?string
    {
        if ($stage === null) {
            return null;
        }

        return self::LEGACY_STAGE_MAP[$stage] ?? $stage;
    }

    public static function stageLabelFor(?string $stage): string
    {
        $normalizedStage = self::normalizedStage($stage);

        return self::STAGE_OPTIONS[$normalizedStage] ?? str($stage ?? 'Sin etapa')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getStageLabelAttribute(): string
    {
        return self::stageLabelFor($this->stage);
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
