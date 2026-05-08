<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Postulaciones</h2>
            <a href="{{ route('applications.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nueva postulacion</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded mb-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Postulante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vacante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etapa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ultima actividad</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($applications as $application)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('applications.show', $application) }}" class="font-medium text-indigo-600">{{ $application->talent->full_name }}</a>
                                    <p class="text-sm text-gray-500">{{ $application->talent->email ?? 'Sin email' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('vacancies.show', $application->vacancy) }}" class="font-medium text-gray-900">{{ $application->vacancy->display_title }}</a>
                                    <p class="text-sm text-gray-500">{{ $application->vacancy->display_company ?? 'Cliente confidencial' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <x-application-status-badge :status="$application->status" />
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <x-application-stage-badge :stage="$application->stage" />
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $application->match_score !== null ? $application->match_score.'%' : 'Sin score' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $application->last_activity_at?->format('d/m/Y H:i') ?? 'Sin actividad' }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('applications.edit', $application) }}" class="text-indigo-600">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">Aun no tienes postulaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>
