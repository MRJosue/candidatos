<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar CV</h2></x-slot>
    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @include('cv._document_import', [
                'action' => route('cv.import-document-ai', $profile),
                'profile' => $profile,
                'talent' => $profile->talent,
            ])

            @include('cv._document_import_preview', [
                'applyAction' => route('cv.apply-document-import', $profile),
            ])

            <section class="bg-white p-6 rounded shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos principales</h3>
                <form method="POST" action="{{ route('cv.update', $profile) }}" class="space-y-4">
                    @method('PUT')
                    @include('cv._form')
                </form>
            </section>

            <section class="bg-white p-6 rounded shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Secciones del CV</h3>
                    <p class="text-sm text-gray-500">Edita cada seccion directamente. En experiencia y educacion usa el formato: Titulo | Organizacion | Periodo.</p>
                </div>

                <form method="POST" action="{{ route('cv.sections.update', $profile) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PUT')

                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Experiencia</span>
                        <textarea name="experiences_text" rows="8" class="mt-1 w-full rounded border-gray-300" placeholder="Tech Lead | Acme Software | 2021 - presente&#10;Descripcion del rol">{{ old('experiences_text', $sectionText['experiences'] ?? '') }}</textarea>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Educacion</span>
                        <textarea name="education_text" rows="6" class="mt-1 w-full rounded border-gray-300" placeholder="Ingenieria en Sistemas | Universidad Demo | 2017 - 2021&#10;Notas opcionales">{{ old('education_text', $sectionText['education'] ?? '') }}</textarea>
                    </label>

                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Habilidades</span>
                            <textarea name="skills_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="Laravel&#10;PHP&#10;MySQL">{{ old('skills_text', $sectionText['skills'] ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Idiomas</span>
                            <textarea name="languages_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="Espanol&#10;Ingles">{{ old('languages_text', $sectionText['languages'] ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Habilidades blandas</span>
                            <textarea name="soft_skills_text" rows="7" class="mt-1 w-full rounded border-gray-300" placeholder="Liderazgo&#10;Comunicacion">{{ old('soft_skills_text', $sectionText['soft_skills'] ?? '') }}</textarea>
                        </label>
                    </div>

                    @error('experiences_text')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar secciones</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
