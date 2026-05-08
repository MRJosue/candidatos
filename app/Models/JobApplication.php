<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    public const DEFAULT_STATUS = 'in_review';

    private const STATUS_OPTIONS = [
        'waiting_feedback' => 'En espera retroalimentación',
        'socioeconomic_study' => 'Estudio socioeconómico',
        'psychometric_tests' => 'Pruebas psicométricas',
        'technical_interview' => 'Entrevista técnica',
        'in_review' => 'En revisión',
        'hr_interview' => 'Entrevista RH',
        'offer_sent' => 'Oferta enviada',
        'hired' => 'Contratado',
        'rejected' => 'Rechazado',
    ];

    private const LEGACY_STATUS_LABELS = [
        'applied' => 'En revisión',
        'active' => 'En espera retroalimentación',
        'withdrawn' => 'Rechazado',
    ];

    private const STATUS_COLORS = [
        'waiting_feedback' => ['background' => '#f3d8e5', 'text' => '#7a3f5f', 'dot' => '#dd5c9a'],
        'socioeconomic_study' => ['background' => '#dcebe3', 'text' => '#3d6f57', 'dot' => '#58ae82'],
        'psychometric_tests' => ['background' => '#f2dcc8', 'text' => '#795335', 'dot' => '#da8b45'],
        'technical_interview' => ['background' => '#ead9c6', 'text' => '#72583d', 'dot' => '#b98557'],
        'in_review' => ['background' => '#d5e5f6', 'text' => '#2d5f87', 'dot' => '#4594dc'],
        'hr_interview' => ['background' => '#e8d6f4', 'text' => '#765099', 'dot' => '#ad68d6'],
        'offer_sent' => ['background' => '#f1e7bf', 'text' => '#786323', 'dot' => '#d7b73f'],
        'hired' => ['background' => '#dcebe3', 'text' => '#3d6f57', 'dot' => '#58ae82'],
        'rejected' => ['background' => '#f1d8d5', 'text' => '#8a4943', 'dot' => '#e26c64'],
    ];

    private const LEGACY_STATUS_COLORS = [
        'applied' => 'in_review',
        'active' => 'waiting_feedback',
        'withdrawn' => 'rejected',
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

    public static function statusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status]
            ?? self::LEGACY_STATUS_LABELS[$this->status]
            ?? str_replace('_', ' ', (string) $this->status);
    }

    public function statusColors(): array
    {
        $status = self::LEGACY_STATUS_COLORS[$this->status] ?? $this->status;

        return self::STATUS_COLORS[$status] ?? [
            'background' => '#f3f4f6',
            'text' => '#4b5563',
            'dot' => '#9ca3af',
        ];
    }
}
