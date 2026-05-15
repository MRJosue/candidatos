<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ApplicationTheme extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'arena';

    public const TOKENS = [
        'bg',
        'surface',
        'surface-muted',
        'surface-soft',
        'border',
        'text',
        'text-muted',
        'accent',
        'accent-hover',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'light_palette',
        'dark_palette',
        'background_image_path',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'light_palette' => 'array',
        'dark_palette' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public static function defaultPalettes(): array
    {
        return [
            'light_palette' => [
                'bg' => '#fbf3e7',
                'surface' => '#fffaf3',
                'surface-muted' => '#f5eadc',
                'surface-soft' => '#fff7ed',
                'border' => '#ead8c2',
                'text' => '#2f261f',
                'text-muted' => '#6f5b49',
                'accent' => '#b45309',
                'accent-hover' => '#92400e',
            ],
            'dark_palette' => [
                'bg' => '#17110d',
                'surface' => '#211814',
                'surface-muted' => '#2b211d',
                'surface-soft' => '#32261f',
                'border' => '#4a372c',
                'text' => '#f6eadb',
                'text-muted' => '#d4bda7',
                'accent' => '#f59e0b',
                'accent-hover' => '#fbbf24',
            ],
        ];
    }

    public static function ensureDefaultThemes(): void
    {
        if (! Schema::hasTable('application_themes')) {
            return;
        }

        $palettes = self::defaultPalettes();

        self::updateOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            [
                'name' => 'Arena',
                'description' => 'Tema calido base de CV Studio.',
                'light_palette' => $palettes['light_palette'],
                'dark_palette' => $palettes['dark_palette'],
                'is_active' => true,
                'is_default' => true,
            ],
        );
    }

    public static function default(): self
    {
        if (! Schema::hasTable('application_themes')) {
            return self::fallback();
        }

        self::ensureDefaultThemes();

        return self::query()
            ->where('is_default', true)
            ->first()
            ?? self::query()->where('is_active', true)->oldest()->first()
            ?? self::fallback();
    }

    public static function fallback(): self
    {
        $palettes = self::defaultPalettes();

        return new self([
            'name' => 'Arena',
            'slug' => self::DEFAULT_SLUG,
            'description' => 'Tema calido base de CV Studio.',
            'light_palette' => $palettes['light_palette'],
            'dark_palette' => $palettes['dark_palette'],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function paletteFor(string $mode): array
    {
        $defaults = self::defaultPalettes();
        $key = $mode === 'dark' ? 'dark_palette' : 'light_palette';

        return array_replace($defaults[$key], $this->{$key} ?? []);
    }

    public function backgroundImageUrl(): ?string
    {
        if (! $this->background_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->background_image_path);
    }
}
