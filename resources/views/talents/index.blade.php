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
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded mb-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <form id="bulk-cv-download" method="POST" action="{{ route('talents.download-cvs') }}">
                @csrf
            </form>

            <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <p id="selected-talents-count" class="text-sm text-gray-500">0 talentos seleccionados</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Formato de descarga</span>
                        <select
                            name="cv_template_slug"
                            form="bulk-cv-download"
                            class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm sm:w-56"
                        >
                            <option value="act-digital" selected>ACT Digital</option>
                            <option value="academico-bullet">Academico bullet</option>
                        </select>
                    </label>
                    <button
                        id="download-selected-cvs"
                        type="submit"
                        form="bulk-cv-download"
                        class="px-4 py-2 bg-gray-900 text-white rounded text-sm disabled:opacity-50"
                        disabled
                    >
                        Descargar CVs seleccionados
                    </button>
                </div>
            </div>

            <div class="bg-white rounded shadow-sm overflow-x-auto">
                <table class="min-w-[1180px] w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    id="select-all-talents"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    aria-label="Seleccionar todos los talentos"
                                >
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CV</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Postulaciones</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nueva postulacion</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-64">Acciones</th>
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
                                    <input
                                        type="checkbox"
                                        name="talent_ids[]"
                                        value="{{ $talent->id }}"
                                        form="bulk-cv-download"
                                        class="talent-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        aria-label="Seleccionar {{ $talent->full_name }}"
                                        @disabled(! $talent->cvProfile)
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('talents.show', $talent) }}" class="font-medium text-indigo-600">{{ $talent->full_name }}</a>
                                    <p class="text-sm text-gray-500">{{ $talent->cvProfile?->email ?? 'Sin CV asociado' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->source ?? 'No definida' }}</td>
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
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-4">
                                        @if ($talent->cvProfile)
                                            <a href="{{ route('cv.download', $talent->cvProfile) }}" class="text-gray-700">Descargar CV</a>
                                            <a href="{{ route('cv.edit', $talent->cvProfile) }}" class="text-gray-700">Editar CV</a>
                                        @else
                                            <form method="POST" action="{{ route('talents.cv.store', $talent) }}">
                                                @csrf
                                                <button type="submit" class="text-gray-700">Crear CV</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('talents.edit', $talent) }}" class="text-indigo-600">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">Aun no tienes talentos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $talents->links() }}</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('select-all-talents');
            const checkboxes = Array.from(document.querySelectorAll('.talent-checkbox'));
            const downloadButton = document.getElementById('download-selected-cvs');
            const selectedCount = document.getElementById('selected-talents-count');

            const refreshBulkState = () => {
                const enabledCheckboxes = checkboxes.filter((checkbox) => ! checkbox.disabled);
                const checkedCheckboxes = enabledCheckboxes.filter((checkbox) => checkbox.checked);

                downloadButton.disabled = checkedCheckboxes.length === 0;
                selectedCount.textContent = `${checkedCheckboxes.length} talento${checkedCheckboxes.length === 1 ? '' : 's'} seleccionado${checkedCheckboxes.length === 1 ? '' : 's'}`;
                selectAll.checked = enabledCheckboxes.length > 0 && checkedCheckboxes.length === enabledCheckboxes.length;
                selectAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < enabledCheckboxes.length;
                selectAll.disabled = enabledCheckboxes.length === 0;
            };

            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    if (! checkbox.disabled) {
                        checkbox.checked = selectAll.checked;
                    }
                });

                refreshBulkState();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshBulkState));
            refreshBulkState();
        });
    </script>
</x-app-layout>
