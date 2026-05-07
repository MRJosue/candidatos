<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vacantes</h2>
            <a href="{{ route('vacancies.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nueva vacante</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            @forelse ($vacancies as $vacancy)
                <div class="bg-white p-6 rounded shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('vacancies.show', $vacancy) }}" class="font-semibold text-gray-900">{{ $vacancy->display_title }}</a>
                            <p class="text-sm text-gray-500">{{ $vacancy->display_company ?? 'Cliente confidencial' }} · {{ $vacancy->position?->location ?? $vacancy->location ?? 'Ubicacion por definir' }}</p>
                        </div>
                        <div class="text-right space-y-1">
                            <p class="text-sm uppercase text-gray-400">{{ $vacancy->status }}</p>
                            <p class="font-semibold">{{ $vacancy->applications_count }} postulantes</p>
                            <a href="{{ route('vacancies.edit', $vacancy) }}" class="text-sm text-indigo-600">Editar</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white p-8 rounded shadow-sm text-center text-gray-500">Aun no tienes vacantes registradas.</div>
            @endforelse

            {{ $vacancies->links() }}
        </div>
    </div>
</x-app-layout>
