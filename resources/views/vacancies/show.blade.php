<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $vacancy->display_title }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('vacancies.edit', $vacancy) }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Editar</a>
                <form method="POST" action="{{ route('vacancies.destroy', $vacancy) }}" onsubmit="return confirm('Eliminar esta vacante?')">
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

            <div class="bg-white p-6 rounded shadow-sm">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-semibold">{{ $vacancy->display_company ?? 'Cliente confidencial' }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ $vacancy->position?->location ?? $vacancy->location ?? 'Ubicacion por definir' }}
                            ·
                            {{ $vacancy->position?->work_mode ?? $vacancy->work_mode ?? 'Modalidad por definir' }}
                        </p>
                    </div>
                    <span class="text-sm uppercase text-gray-400">{{ $vacancy->status }}</span>
                </div>
                <dl class="grid md:grid-cols-3 gap-4 text-sm mb-5">
                    <div>
                        <dt class="text-gray-500">Compania</dt>
                        <dd>{{ $vacancy->display_company ?? 'Cliente confidencial' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Puesto</dt>
                        <dd>{{ $vacancy->display_title }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Senioridad</dt>
                        <dd>{{ $vacancy->position?->seniority ?? $vacancy->seniority ?? 'No definida' }}</dd>
                    </div>
                </dl>
                <p class="text-gray-700 whitespace-pre-line">{{ $vacancy->position?->description ?? $vacancy->description ?: 'Sin descripcion.' }}</p>
            </div>

            <div class="bg-white p-6 rounded shadow-sm">
                <h3 class="font-semibold mb-4">Postulantes</h3>
                <div class="space-y-3">
                    @forelse ($vacancy->applications as $application)
                        <div class="flex items-center justify-between border-b pb-3 last:border-b-0">
                            <div>
                                <a href="{{ route('talents.show', $application->talent) }}" class="font-medium text-indigo-600">{{ $application->talent->full_name }}</a>
                                <p class="text-sm text-gray-500">
                                    {{ $vacancy->display_title }} · {{ $vacancy->display_company ?? 'Cliente confidencial' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <x-application-stage-badge :stage="$application->stage" />
                                <p class="text-xs text-gray-500">{{ $application->cvProfile?->title ?? 'Sin CV asociado' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">Esta vacante aun no tiene postulantes.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
