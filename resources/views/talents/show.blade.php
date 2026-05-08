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
                    <h3 class="font-semibold mb-4">Informacion tecnica</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $talent->technical_summary ?: 'Sin resumen tecnico.' }}</p>

                    @if ($talent->technical_stack)
                        <div class="flex flex-wrap gap-2 mt-5">
                            @foreach ($talent->technical_stack as $skill)
                                <span class="px-3 py-1 rounded bg-gray-100 text-sm text-gray-700">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Datos del talento</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Email</dt><dd>{{ $talent->email ?? 'Sin email' }}</dd></div>
                        <div><dt class="text-gray-500">Telefono</dt><dd>{{ $talent->phone ?? 'Sin telefono' }}</dd></div>
                        <div><dt class="text-gray-500">Ubicacion</dt><dd>{{ $talent->location ?? 'Sin ubicacion' }}</dd></div>
                        <div><dt class="text-gray-500">Disponibilidad</dt><dd>{{ $talent->availability ?? 'No definida' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="font-semibold">CV</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($talent->cvProfile)
                                <a href="{{ route('cv.edit', $talent->cvProfile) }}" class="px-3 py-2 bg-gray-900 text-white rounded text-sm">Editar CV</a>
                            @else
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
                        @if ($talent->cvProfile)
                            <a href="{{ route('cv.show', $talent->cvProfile) }}" class="flex justify-between border-b pb-3 last:border-b-0">
                                <span>{{ $talent->cvProfile->title }}</span>
                                <span class="text-sm text-gray-500">{{ $talent->cvProfile->template?->name ?? 'Sin plantilla' }}</span>
                            </a>
                        @else
                            <p class="text-gray-500">Este talento aun no tiene CV asociado.</p>
                        @endif
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
                                @php($colors = $application->statusColors())
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-medium" style="background-color: {{ $colors['background'] }}; color: {{ $colors['text'] }};">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $colors['dot'] }};"></span>
                                    {{ $application->statusLabel() }}
                                </span>
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
