<?php

namespace App\Http\Controllers;

use App\Models\ApplicationTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationThemeController extends Controller
{
    public function index(): View
    {
        return view('admin.themes.index', [
            'themes' => ApplicationTheme::query()->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.themes.create', [
            'theme' => ApplicationTheme::fallback(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedTheme($request);

        $theme = ApplicationTheme::create($data);
        $this->syncDefaultTheme($theme, $request->boolean('is_default'));

        return redirect()->route('admin.themes.index')->with('status', 'theme-saved');
    }

    public function importJson(Request $request): RedirectResponse
    {
        return redirect()
            ->route('admin.themes.create')
            ->withInput($this->themeDataFromJsonPayload($request))
            ->with('status', 'theme-json-loaded');
    }

    public function edit(ApplicationTheme $theme): View
    {
        return view('admin.themes.edit', [
            'theme' => $theme,
        ]);
    }

    public function update(Request $request, ApplicationTheme $theme): RedirectResponse
    {
        $data = $this->validatedTheme($request, $theme);

        if ($request->boolean('remove_background') && $theme->background_image_path) {
            Storage::disk('public')->delete($theme->background_image_path);
            $data['background_image_path'] = null;
        }

        $theme->update($data);
        $this->syncDefaultTheme($theme, $request->boolean('is_default'));

        return redirect()->route('admin.themes.edit', $theme)->with('status', 'theme-saved');
    }

    public function destroy(ApplicationTheme $theme): RedirectResponse
    {
        if ($theme->is_default) {
            return back()->withErrors(['theme' => 'No puedes eliminar el tema predeterminado.']);
        }

        if ($theme->background_image_path) {
            Storage::disk('public')->delete($theme->background_image_path);
        }

        $theme->delete();

        return redirect()->route('admin.themes.index')->with('status', 'theme-deleted');
    }

    private function validatedTheme(Request $request, ?ApplicationTheme $theme = null): array
    {
        $this->mergeThemeJsonPayload($request);

        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'slug' => [
                'required',
                'string',
                'max:80',
                Rule::unique('application_themes', 'slug')->ignore($theme),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'background_image' => ['nullable', 'image', 'max:4096'],
            'remove_background' => ['nullable', 'boolean'],
        ];

        foreach (['light_palette', 'dark_palette'] as $palette) {
            foreach (ApplicationTheme::TOKENS as $token) {
                $rules["{$palette}.{$token}"] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            }
        }

        $validated = $request->validate($rules);

        $data = Arr::only($validated, ['name', 'description', 'light_palette', 'dark_palette']);
        $data['slug'] = $validated['slug'];
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($request->hasFile('background_image')) {
            if ($theme?->background_image_path) {
                Storage::disk('public')->delete($theme->background_image_path);
            }

            $data['background_image_path'] = $request->file('background_image')->store('theme-backgrounds', 'public');
        }

        return $data;
    }

    private function mergeThemeJsonPayload(Request $request): void
    {
        if (! $request->filled('theme_json') && ! $request->hasFile('theme_json_file')) {
            return;
        }

        $request->merge($this->themeDataFromJsonPayload($request));
    }

    private function themeDataFromJsonPayload(Request $request): array
    {
        $json = trim((string) $request->input('theme_json', ''));

        if ($request->hasFile('theme_json_file')) {
            $json = $request->file('theme_json_file')->get();
        }

        if ($json === '') {
            throw ValidationException::withMessages([
                'theme_json' => 'Pega un JSON o sube un archivo .json.',
            ]);
        }

        $payload = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
            throw ValidationException::withMessages([
                'theme_json' => 'El JSON del tema no es valido.',
            ]);
        }

        $lightPalette = $payload['light_palette'] ?? $payload['light'] ?? null;
        $darkPalette = $payload['dark_palette'] ?? $payload['dark'] ?? null;

        if (! is_array($lightPalette) || ! is_array($darkPalette)) {
            throw ValidationException::withMessages([
                'theme_json' => 'El JSON debe incluir light_palette y dark_palette.',
            ]);
        }

        return [
            'name' => $payload['name'] ?? $request->input('name'),
            'slug' => $payload['slug'] ?? $request->input('slug'),
            'description' => $payload['description'] ?? $request->input('description'),
            'is_active' => $payload['is_active'] ?? $request->input('is_active', true),
            'is_default' => $payload['is_default'] ?? $request->input('is_default', false),
            'light_palette' => $this->onlyThemeTokens($lightPalette),
            'dark_palette' => $this->onlyThemeTokens($darkPalette),
        ];
    }

    private function onlyThemeTokens(array $palette): array
    {
        return Arr::only($palette, ApplicationTheme::TOKENS);
    }

    private function syncDefaultTheme(ApplicationTheme $theme, bool $isDefault): void
    {
        if (! $isDefault) {
            if (! ApplicationTheme::query()->where('is_default', true)->exists()) {
                $theme->forceFill(['is_default' => true, 'is_active' => true])->save();
            }

            return;
        }

        ApplicationTheme::query()
            ->whereKeyNot($theme->getKey())
            ->update(['is_default' => false]);

        $theme->forceFill(['is_default' => true, 'is_active' => true])->save();
    }
}
