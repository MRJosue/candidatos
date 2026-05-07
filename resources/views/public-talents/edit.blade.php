<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'CV Studio') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @wireUiScripts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
    <div class="w-full max-w-5xl mx-auto py-8 px-4">
        <div class="bg-white p-6 rounded shadow-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Actualizar informacion</h1>
                <p class="mt-1 text-sm text-gray-500">Completa tus datos profesionales para mantener tu CV actualizado.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('public-talents.update', ['talent' => $talent->public_token]) }}" class="space-y-8">
                @csrf
                @method('PUT')

                <section class="space-y-4">
                    <h2 class="font-semibold text-gray-900">Datos personales</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-gray-700">Nombre</span>
                            <input name="first_name" value="{{ old('first_name', $talent->first_name) }}" class="mt-1 w-full rounded border-gray-300" required>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Apellido</span>
                            <input name="last_name" value="{{ old('last_name', $talent->last_name) }}" class="mt-1 w-full rounded border-gray-300" required>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Email</span>
                            <input type="email" name="email" value="{{ old('email', $talent->email) }}" class="mt-1 w-full rounded border-gray-300" required>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Telefono</span>
                            <input name="phone" value="{{ old('phone', $talent->phone) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Ubicacion</span>
                            <input name="location" value="{{ old('location', $talent->location) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Disponibilidad</span>
                            <input name="availability" value="{{ old('availability', $talent->availability) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                    </div>
                </section>

                <section class="space-y-4">
                    <h2 class="font-semibold text-gray-900">Perfil profesional</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Headline</span>
                            <input name="headline" value="{{ old('headline', $talent->headline) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Puesto objetivo</span>
                            <input name="target_position" value="{{ old('target_position', $talent->target_position) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Senioridad</span>
                            <input name="seniority" value="{{ old('seniority', $talent->seniority) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Stack tecnico</span>
                            <textarea name="technical_stack" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('technical_stack', is_array($talent->technical_stack) ? implode(', ', $talent->technical_stack) : '') }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Idiomas</span>
                            <textarea name="languages" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('languages', is_array($talent->languages) ? implode(', ', $talent->languages) : '') }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Links</span>
                            <textarea name="links" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('links', is_array($talent->links) ? implode("\n", $talent->links) : '') }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Resumen tecnico</span>
                            <textarea name="technical_summary" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('technical_summary', $talent->technical_summary) }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="space-y-4">
                    <h2 class="font-semibold text-gray-900">Datos del CV</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-gray-700">Titulo del CV</span>
                            <input name="cv_title" value="{{ old('cv_title', $profile->title) }}" class="mt-1 w-full rounded border-gray-300" required>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Lema o frase breve</span>
                            <input name="tagline" value="{{ old('tagline', $profile->tagline) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Resumen profesional</span>
                            <textarea name="summary" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('summary', $profile->summary) }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Objetivo profesional</span>
                            <textarea name="objective" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('objective', $profile->objective) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Titulo de habilidades</span>
                            <input name="skills_section_title" value="{{ old('skills_section_title', $profile->skills_section_title ?? 'Habilidades') }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Titulo de habilidades blandas</span>
                            <input name="soft_skills_section_title" value="{{ old('soft_skills_section_title', $profile->soft_skills_section_title ?? 'Habilidades blandas') }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Premios y reconocimientos</span>
                            <textarea name="awards" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('awards', $profile->awards) }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Liderazgo y actividades</span>
                            <textarea name="leadership_activities" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('leadership_activities', $profile->leadership_activities) }}</textarea>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Intereses</span>
                            <textarea name="interests" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('interests', $profile->interests) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">LinkedIn</span>
                            <input name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Portafolio</span>
                            <input name="portfolio_url" value="{{ old('portfolio_url', $profile->portfolio_url) }}" class="mt-1 w-full rounded border-gray-300">
                        </label>
                    </div>
                </section>

                @if ($errors->any())
                    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar informacion</button>
            </form>
        </div>
    </div>
    </body>
</html>
