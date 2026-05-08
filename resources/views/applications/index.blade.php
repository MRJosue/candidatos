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

            <form method="GET" action="{{ route('applications.index') }}" class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <input type="hidden" name="talent_id" value="{{ $filters['talent_id'] }}">
                <input type="hidden" name="vacancy_id" value="{{ $filters['vacancy_id'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <input type="hidden" name="stage" value="{{ $filters['stage'] }}">
                <input type="hidden" name="match_score" value="{{ $filters['match_score'] }}">
                <input type="hidden" name="last_activity_date" value="{{ $filters['last_activity_date'] }}">

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <span>Mostrar</span>
                    <select name="per_page" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span>registros</span>
                </label>

                <div class="flex items-center gap-2">
                    <a href="{{ route('applications.index') }}" class="px-4 py-2 bg-white text-gray-700 rounded border border-gray-300 text-sm">Limpiar filtros</a>
                    <a href="{{ route('applications.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 text-white rounded text-sm">Exportar Excel</a>
                </div>
            </form>

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <form method="GET" action="{{ route('applications.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Postulante</span>
                                <select name="talent_id" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    @foreach ($filterOptions['talents'] as $talent)
                                        <option value="{{ $talent['id'] }}" @selected((string) $filters['talent_id'] === (string) $talent['id'])>{{ $talent['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Vacante</span>
                                <select name="vacancy_id" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todas</option>
                                    @foreach ($filterOptions['vacancies'] as $vacancy)
                                        <option value="{{ $vacancy['id'] }}" @selected((string) $filters['vacancy_id'] === (string) $vacancy['id'])>{{ $vacancy['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Estado</span>
                                <select name="status" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    @foreach ($filterOptions['statuses'] as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Etapa</span>
                                <select name="stage" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todas</option>
                                    @foreach ($filterOptions['stages'] as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['stage'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Match</span>
                                <select name="match_score" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    @foreach ($filterOptions['matchScores'] as $score)
                                        <option value="{{ $score }}" @selected((string) $filters['match_score'] === (string) $score)>{{ $score }}%</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Ultima actividad</span>
                                <select name="last_activity_date" class="mt-2 block w-full rounded border-gray-300 text-xs normal-case font-normal" onchange="this.form.submit()">
                                    <option value="">Todas</option>
                                    @foreach ($filterOptions['lastActivityDates'] as $date)
                                        <option value="{{ $date }}" @selected($filters['last_activity_date'] === $date)>{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</option>
                                    @endforeach
                                </select>
                            </th>
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
                </form>
            </div>

            <div class="mt-6">{{ $applications->links() }}</div>
        </div>
    </div>
</x-app-layout>
