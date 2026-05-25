<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $talent->full_name }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('talents.edit', $talent) }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Editar</a>
                <form method="POST" action="{{ route('talents.destroy', $talent) }}" onsubmit="return confirm('Eliminar este postulante?')">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 bg-red-50 text-red-700 rounded text-sm">Eliminar</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Notas internas</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $talent->notes ?: 'Sin notas internas.' }}</p>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Ficha minima</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Estado</dt><dd class="capitalize">{{ $talent->status }}</dd></div>
                        <div><dt class="text-gray-500">Fuente</dt><dd>{{ $talent->source ?? 'No definida' }}</dd></div>
                        <div><dt class="text-gray-500">Ultimo contacto</dt><dd>{{ $talent->last_contacted_at?->format('d/m/Y') ?? 'No registrado' }}</dd></div>
                        <div><dt class="text-gray-500">Contacto de CV</dt><dd>{{ $talent->cvProfile?->email ?? 'Sin CV asociado' }}</dd></div>
                        <div><dt class="text-gray-500">CVs</dt><dd>{{ $talent->cvProfiles->count() }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="font-semibold">CV</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($talent->cvProfiles->count() < \App\Models\CvProfile::MAX_PER_TALENT)
                                <a href="{{ route('talents.cv.create', $talent) }}" class="px-3 py-2 bg-gray-900 text-white rounded text-sm">Crear CV</a>
                            @endif
                            <button
                                type="button"
                                x-data="{ copied: false, link: @js(route('public-talents.edit', ['talent' => $talent->public_token])) }"
                                x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 1800)"
                                class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded text-sm"
                            >
                                <span x-show="! copied">Copiar liga para postulante</span>
                                <span x-show="copied" x-cloak>Copiada</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @forelse ($talent->cvProfiles as $cvProfile)
                            <div class="flex flex-col gap-3 border-b pb-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                <a href="{{ route('cv.show', $cvProfile) }}" class="min-w-0">
                                    <span class="font-medium text-indigo-700">
                                        {{ $cvProfile->title }}
                                        @if ($cvProfile->is_primary)
                                            <span class="ml-2 rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Principal</span>
                                        @endif
                                    </span>
                                    <span class="block text-sm text-gray-500">{{ $cvProfile->template?->name ?? 'Sin plantilla' }} · {{ $cvProfile->languageLabel() }}</span>
                                </a>
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    <a href="{{ route('cv.edit', $cvProfile) }}" class="text-indigo-700">Editar</a>
                                    <a href="{{ route('cv.download', ['cvProfile' => $cvProfile, 'language' => 'es']) }}" class="text-gray-700">Descargar ES</a>
                                    <a href="{{ route('cv.download', ['cvProfile' => $cvProfile, 'language' => 'en']) }}" class="text-gray-700">Descargar EN</a>
                                    <form method="POST" action="{{ route('cv.destroy', $cvProfile) }}" onsubmit="return confirm('¿Eliminar este CV? Esta accion no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="talent">
                                        <button class="text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">Este talento aun no tiene CV asociado.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Postulaciones</h3>
                    <div class="space-y-3">
                        @forelse ($talent->applications as $application)
                            <a href="{{ route('vacancies.show', $application->vacancy) }}" class="flex justify-between border-b pb-3 last:border-b-0">
                                <span>
                                    {{ $application->vacancy->display_title }}
                                    <span class="block text-sm text-gray-500">{{ $application->vacancy->display_company ?? 'Cliente confidencial' }}</span>
                                </span>
                                <x-application-stage-badge :stage="$application->stage" class="shrink-0" />
                            </a>
                        @empty
                            <p class="text-gray-500">Sin postulaciones registradas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
