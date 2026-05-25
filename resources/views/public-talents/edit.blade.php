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
            <div class="mb-6 bg-white p-6 rounded shadow-sm">
                <h1 class="text-2xl font-semibold text-gray-900">Actualizar informacion</h1>
                <p class="mt-1 text-sm text-gray-500">Completa tus datos profesionales para mantener tu CV actualizado.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            @if ($linkUsed)
                <div class="mb-5 bg-amber-50 text-amber-900 p-4 rounded">
                    Esta liga ya fue utilizada. Si necesitas hacer cambios, solicita una nueva liga al reclutador.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 bg-red-50 text-red-800 p-4 rounded">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('public-talents.update', ['talent' => $talent->public_token]) }}" class="space-y-6">
                @method('PUT')

                <fieldset @disabled($linkUsed) class="space-y-6">
                    <section class="bg-white p-6 rounded shadow-sm space-y-4">
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

                    <section class="bg-white p-6 rounded shadow-sm space-y-4">
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

                    <section class="bg-white p-6 rounded shadow-sm space-y-4">
                        <h2 class="font-semibold text-gray-900">Datos del CV</h2>
                        @include('cv._form', ['showSubmitButton' => false])
                    </section>

                    @include('cv._sections_form')
                    @include('cv._other_fields')
                </fieldset>

                @unless ($linkUsed)
                    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar informacion</button>
                @endunless
            </form>
        </div>
    </body>
</html>
