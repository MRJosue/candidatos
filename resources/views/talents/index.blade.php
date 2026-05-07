<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Talentos</h2>
            <div class="flex items-center gap-2">
                <a href="/talents/import" class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm">Carga masiva</a>
                <a href="{{ route('talents.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nuevo postulante</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded mb-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perfil tecnico</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CV</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Postulaciones</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nueva postulacion</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($talents as $talent)
                            @php
                                $appliedVacancyIds = $talent->applications->pluck('vacancy_id')->all();
                                $hasAvailableVacancies = $vacancies->contains(fn ($vacancy) => ! in_array($vacancy->id, $appliedVacancyIds, true));
                            @endphp
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('talents.show', $talent) }}" class="font-medium text-indigo-600">{{ $talent->full_name }}</a>
                                    <p class="text-sm text-gray-500">{{ $talent->email }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->target_position ?? $talent->headline ?? 'Sin perfil definido' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 capitalize">{{ $talent->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->cvProfile ? 'Asociado' : 'Pendiente' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->applications_count }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <form method="POST" action="{{ route('talents.applications.store', $talent) }}" class="flex items-center gap-2">
                                        @csrf
                                        <select name="vacancy_id" class="w-56 rounded border-gray-300 text-sm" @disabled(! $hasAvailableVacancies)>
                                            <option value="">Selecciona vacante</option>
                                            @foreach ($vacancies as $vacancy)
                                                <option value="{{ $vacancy->id }}" @disabled(in_array($vacancy->id, $appliedVacancyIds, true))>
                                                    {{ $vacancy->display_title }} - {{ $vacancy->display_company ?? 'Cliente confidencial' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="px-3 py-2 bg-gray-900 text-white rounded text-sm disabled:opacity-50" @disabled(! $hasAvailableVacancies)>Postular</button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('talents.edit', $talent) }}" class="text-indigo-600">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">Aun no tienes talentos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $talents->links() }}</div>
        </div>
    </div>
</x-app-layout>
