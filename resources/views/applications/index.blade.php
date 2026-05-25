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

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <form id="bulk-application-cv-download" method="POST" action="{{ route('applications.download-cvs') }}">
                @csrf
            </form>

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

            <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <p id="selected-applications-count" class="text-sm text-gray-500">0 postulaciones seleccionadas</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Idioma</span>
                        <select
                            name="cv_language"
                            form="bulk-application-cv-download"
                            class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm sm:w-40"
                        >
                            <option value="es" selected>Español</option>
                            <option value="en">Inglés</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Formato de descarga</span>
                        <select
                            name="cv_template_slug"
                            form="bulk-application-cv-download"
                            class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm sm:w-56"
                        >
                            <option value="act-digital" selected>ACT Digital</option>
                            <option value="academico-bullet">Academico bullet</option>
                        </select>
                    </label>
                    <button
                        id="download-selected-application-cvs"
                        type="submit"
                        form="bulk-application-cv-download"
                        class="px-4 py-2 bg-gray-900 text-white rounded text-sm disabled:opacity-50"
                        disabled
                    >
                        Descargar CVs seleccionados
                    </button>
                </div>
            </div>

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <form method="GET" action="{{ route('applications.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    id="select-all-applications"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    aria-label="Seleccionar todas las postulaciones"
                                >
                            </th>
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
                            @php
                                $applicationCv = $application->cvProfile ?? $application->talent->cvProfile;
                            @endphp
                            <tr>
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        name="application_ids[]"
                                        value="{{ $application->id }}"
                                        form="bulk-application-cv-download"
                                        class="application-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        aria-label="Seleccionar postulacion de {{ $application->talent->full_name }}"
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('applications.show', $application) }}" class="font-medium text-indigo-600">{{ $application->talent->full_name }}</a>
                                    <p class="text-sm text-gray-500">{{ $application->contact_email ?? 'Sin email' }}</p>
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
                                    <div class="flex items-center justify-end gap-4">
                                        @if ($applicationCv)
                                            <a href="{{ route('cv.download', ['cvProfile' => $applicationCv, 'language' => 'es']) }}" class="text-gray-700">Descargar ES</a>
                                            <a href="{{ route('cv.download', ['cvProfile' => $applicationCv, 'language' => 'en']) }}" class="text-gray-700">Descargar EN</a>
                                        @endif
                                        <a href="{{ route('applications.edit', $application) }}" class="text-indigo-600">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">Aun no tienes postulaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </form>
            </div>

            <div class="mt-6">{{ $applications->links() }}</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-applications');
            const checkboxes = Array.from(document.querySelectorAll('.application-checkbox'));
            const downloadButton = document.getElementById('download-selected-application-cvs');
            const selectedCount = document.getElementById('selected-applications-count');

            const refreshBulkState = () => {
                const checkedCheckboxes = checkboxes.filter((checkbox) => checkbox.checked);

                downloadButton.disabled = checkedCheckboxes.length === 0;
                selectedCount.textContent = `${checkedCheckboxes.length} postulacion${checkedCheckboxes.length === 1 ? '' : 'es'} seleccionada${checkedCheckboxes.length === 1 ? '' : 's'}`;
                selectAll.checked = checkboxes.length > 0 && checkedCheckboxes.length === checkboxes.length;
                selectAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < checkboxes.length;
                selectAll.disabled = checkboxes.length === 0;
            };

            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });

                refreshBulkState();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshBulkState));
            refreshBulkState();
        });
    </script>
</x-app-layout>
