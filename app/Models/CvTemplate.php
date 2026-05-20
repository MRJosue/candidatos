<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplate extends Model
{
    public const DEFAULT_TEMPLATES = [
        'clasico-profesional' => [
            'name' => 'Clasico profesional',
            'description' => 'Plantilla limpia para perfiles corporativos y administrativos.',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'MXN',
            'is_active' => false,
        ],
        'ejecutivo-premium' => [
            'name' => 'Ejecutivo premium',
            'description' => 'Plantilla premium para perfiles senior, liderazgo y consultoria.',
            'is_premium' => true,
            'price_cents' => 29900,
            'currency' => 'MXN',
            'is_active' => false,
        ],
        'academico-bullet' => [
            'name' => 'Academico bullet',
            'description' => 'Formato academico de una columna con secciones claras y bullets por logro.',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'MXN',
            'is_active' => true,
        ],
        'creativo-sidebar' => [
            'name' => 'Creativo con barra lateral',
            'description' => 'Formato visual con barra lateral para contacto, premios, habilidades e intereses, sin iconos.',
            'is_premium' => true,
            'price_cents' => 29900,
            'currency' => 'MXN',
            'is_active' => false,
        ],
        'act-digital' => [
            'name' => 'ACT Digital',
            'description' => 'Formato corporativo inspirado en ACT Digital, con logo, acentos azules y secciones tabulares.',
            'preview_image_path' => 'images/cv-templates/act-digital-logo.png',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'MXN',
            'is_active' => true,
        ],
    ];

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

    public static function ensureDefaultTemplates(): void
    {
        foreach (self::DEFAULT_TEMPLATES as $slug => $template) {
            self::updateOrCreate(['slug' => $slug], $template);
        }
    }

    public static function defaultTemplate(): ?self
    {
        return self::query()
            ->where('slug', 'act-digital')
            ->where('is_active', true)
            ->first();
    }
}
