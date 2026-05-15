<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ApplicationTheme extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'cv-studio';

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
                'bg' => '#f5f8fc',
                'surface' => '#ffffff',
                'surface-muted' => '#edf3f9',
                'surface-soft' => '#f7f9fc',
                'border' => '#d7e1ee',
                'text' => '#172033',
                'text-muted' => '#5f6f89',
                'accent' => '#3157d5',
                'accent-hover' => '#1f3f9f',
            ],
            'dark_palette' => [
                'bg' => '#0f1726',
                'surface' => '#172033',
                'surface-muted' => '#1f2d46',
                'surface-soft' => '#263754',
                'border' => '#3b4c68',
                'text' => '#f5f8fc',
                'text-muted' => '#b9c6d8',
                'accent' => '#6f8cff',
                'accent-hover' => '#9db2ff',
            ],
        ];
    }

    public static function ensureDefaultThemes(): void
    {
        if (! Schema::hasTable('application_themes')) {
            return;
        }

        $palettes = self::defaultPalettes();

        $theme = self::updateOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            [
                'name' => 'CV Studio',
                'description' => 'Tema claro base inspirado en la identidad visual de CV Studio.',
                'light_palette' => $palettes['light_palette'],
                'dark_palette' => $palettes['dark_palette'],
                'is_active' => true,
                'is_default' => true,
            ],
        );

        self::query()
            ->whereKeyNot($theme->getKey())
            ->where('is_default', true)
            ->update(['is_default' => false]);
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
            'name' => 'CV Studio',
            'slug' => self::DEFAULT_SLUG,
            'description' => 'Tema claro base inspirado en la identidad visual de CV Studio.',
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
