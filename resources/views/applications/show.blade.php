<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Postulacion</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('applications.edit', $application) }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Editar</a>
                <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Eliminar esta postulacion?')">
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
                    <h3 class="font-semibold mb-4">Seguimiento</h3>
                    <dl class="grid md:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Estado</dt><dd class="capitalize">{{ str_replace('_', ' ', $application->status) }}</dd></div>
                        <div><dt class="text-gray-500">Etapa</dt><dd class="capitalize">{{ str_replace('_', ' ', $application->stage) }}</dd></div>
                        <div><dt class="text-gray-500">Match</dt><dd>{{ $application->match_score !== null ? $application->match_score.'%' : 'Sin score' }}</dd></div>
                        <div><dt class="text-gray-500">CV</dt><dd>{{ $application->cvProfile?->title ?? 'Sin CV asociado' }}</dd></div>
                        <div><dt class="text-gray-500">Fecha de postulacion</dt><dd>{{ $application->applied_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd></div>
                        <div><dt class="text-gray-500">Ultima actividad</dt><dd>{{ $application->last_activity_at?->format('d/m/Y H:i') ?? 'Sin actividad' }}</dd></div>
                    </dl>
                    <h3 class="font-semibold mt-6 mb-4">Notas</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $application->notes ?: 'Sin notas registradas.' }}</p>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Relacion</h3>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Postulante</dt>
                            <dd><a href="{{ route('talents.show', $application->talent) }}" class="text-indigo-600">{{ $application->talent->full_name }}</a></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Vacante</dt>
                            <dd><a href="{{ route('vacancies.show', $application->vacancy) }}" class="text-indigo-600">{{ $application->vacancy->display_title }}</a></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Compania</dt>
                            <dd>{{ $application->vacancy->display_company ?? 'Cliente confidencial' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
