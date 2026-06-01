<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Talentos</h2>
            <div class="flex items-center gap-2">
                <a href="/talents/import" class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm">Carga masiva</a>
                <a href="{{ route('talents.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nuevo talento</a>
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
            <form id="talent-filters" method="GET" action="{{ route('talents.index') }}"></form>

            <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <p id="selected-talents-count" class="text-sm text-gray-500">0 talentos seleccionados</p>
                    <button type="submit" form="talent-filters" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50">Buscar</button>
                    @if ($filters['name'] !== '' || $filters['created_date'] !== '')
                        <a href="{{ route('talents.index') }}" class="rounded bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">Limpiar</a>
                    @endif
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Idioma</span>
                        <select
                            name="cv_language"
                            form="bulk-cv-download"
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
                            form="bulk-cv-download"
                            class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm sm:w-56"
                        >
                            <option value="act-digital" selected>ACT Digital</option>
                            <option value="academico-bullet">Academico bullet</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Archivo</span>
                        <select
                            name="file_format"
                            form="bulk-cv-download"
                            class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm sm:w-32"
                        >
                            <option value="pdf" selected>PDF</option>
                            <option value="word">Word</option>
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
                <table class="min-w-[1040px] w-full divide-y divide-gray-200">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Nombre</span>
                                <input
                                    type="search"
                                    name="name"
                                    value="{{ $filters['name'] }}"
                                    form="talent-filters"
                                    placeholder="Buscar nombre"
                                    class="mt-2 block w-56 rounded border-gray-300 text-xs normal-case font-normal"
                                    aria-label="Buscar talento por nombre"
                                >
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <span>Fecha de creacion</span>
                                <input
                                    type="search"
                                    name="created_date"
                                    value="{{ $filters['created_date'] }}"
                                    form="talent-filters"
                                    placeholder="dd/mm/aaaa"
                                    class="mt-2 block w-36 rounded border-gray-300 text-xs normal-case font-normal"
                                    list="talent-created-date-options"
                                    aria-label="Buscar talento por fecha de creacion"
                                >
                                <datalist id="talent-created-date-options">
                                    @foreach ($filterOptions['createdDates'] as $date)
                                        <option value="{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}"></option>
                                    @endforeach
                                </datalist>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CV</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Postulaciones</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-64">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($talents as $talent)
                            @php
                                $appliedVacancyIds = $talent->applications->pluck('vacancy_id')->all();
                                $hasAvailableVacancies = $vacancies->contains(fn ($vacancy) => ! in_array($vacancy->id, $appliedVacancyIds, true));
                                $spanishCv = $talent->cvProfiles->first(fn ($profile) => ($profile->language ?: 'es') === 'es');
                                $englishCv = $talent->cvProfiles->first(fn ($profile) => ($profile->language ?: 'es') === 'en');
                                $canManageTalent = $talent->recruiter_id === auth()->id();
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
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('talents.show', $talent) }}" class="font-medium text-indigo-600">{{ $talent->full_name }}</a>
                                    <p class="text-sm text-gray-500">{{ $talent->cvProfile?->email ?? 'Sin CV asociado' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->created_at?->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->source ?? 'No definida' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 capitalize">{{ $talent->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $talent->cvProfiles->isNotEmpty() ? $talent->cvProfiles->count().' CV(s)' : 'Pendiente' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $talent->applications_count }}</td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                    <div
                                        class="inline-block"
                                        x-data="{
                                            open: false,
                                            top: 0,
                                            left: 0,
                                            place() {
                                                const rect = this.$refs.trigger.getBoundingClientRect();
                                                const menuHeight = this.$refs.menu?.offsetHeight || 0;
                                                const menuWidth = this.$refs.menu?.offsetWidth || 256;
                                                const viewportPadding = 12;
                                                const bottomTop = rect.bottom + 8;
                                                const topTop = rect.top - menuHeight - 8;

                                                this.top = bottomTop + menuHeight > window.innerHeight - viewportPadding
                                                    ? Math.max(viewportPadding, topTop)
                                                    : bottomTop;
                                                this.left = Math.max(viewportPadding, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - viewportPadding));
                                            },
                                            toggle() {
                                                this.open = ! this.open;
                                                if (this.open) this.$nextTick(() => this.place());
                                            }
                                        }"
                                        x-on:keydown.escape.window="open = false"
                                        x-on:resize.window="if (open) place()"
                                        x-on:scroll.window="if (open) place()"
                                    >
                                        <button
                                            type="button"
                                            x-ref="trigger"
                                            x-on:click="toggle()"
                                            class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                            aria-haspopup="menu"
                                            x-bind:aria-expanded="open.toString()"
                                        >
                                            Acciones
                                            <svg class="ml-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-ref="menu"
                                            x-show="open"
                                            x-transition
                                            x-on:click.outside="open = false"
                                            x-bind:style="`top: ${top}px; left: ${left}px;`"
                                            class="fixed z-50 w-64 space-y-1 rounded-md bg-white p-2 text-left shadow-lg ring-1 ring-black ring-opacity-5"
                                            role="menu"
                                        >
                                            <a href="{{ route('talents.show', $talent) }}" class="flex w-full items-center gap-2 rounded bg-sky-50 px-3 py-2 text-start text-sm font-medium leading-5 text-sky-700 transition duration-150 ease-in-out hover:bg-sky-100" role="menuitem">
                                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.411A7.002 7.002 0 0 0 3.465 14.493Z" />
                                                </svg>
                                                Ver talento
                                            </a>
                                            @if ($canManageTalent)
                                                <a href="{{ route('talents.edit', $talent) }}" class="flex w-full items-center gap-2 rounded bg-amber-50 px-3 py-2 text-start text-sm font-medium leading-5 text-amber-700 transition duration-150 ease-in-out hover:bg-amber-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="m13.586 3.586 2.828 2.828-8.486 8.486-3.535.707.707-3.535 8.486-8.486Z" />
                                                        <path d="M3 17a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Z" />
                                                    </svg>
                                                    Editar talento
                                                </a>
                                                <button type="button" onclick="document.getElementById('create-application-{{ $talent->id }}').showModal()" class="flex w-full items-center gap-2 rounded bg-violet-50 px-3 py-2 text-start text-sm font-medium leading-5 text-violet-700 transition duration-150 ease-in-out hover:bg-violet-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 0 1 8.75 1h2.5A2.75 2.75 0 0 1 14 3.75V4h1.25A2.75 2.75 0 0 1 18 6.75v1.085A11.03 11.03 0 0 1 10 11a11.03 11.03 0 0 1-8-3.165V6.75A2.75 2.75 0 0 1 4.75 4H6v-.25ZM8 4h4v-.25A.75.75 0 0 0 11.25 3h-2.5A.75.75 0 0 0 8 3.75V4Z" clip-rule="evenodd" />
                                                        <path d="M2 10.34V14.25A2.75 2.75 0 0 0 4.75 17h10.5A2.75 2.75 0 0 0 18 14.25v-3.91A13.01 13.01 0 0 1 10 13a13.01 13.01 0 0 1-8-2.66Z" />
                                                    </svg>
                                                    Crear postulacion
                                                </button>
                                            @endif
                                            @if ($spanishCv)
                                                <a href="{{ route('cv.show', $spanishCv) }}" class="flex w-full items-center gap-2 rounded bg-emerald-50 px-3 py-2 text-start text-sm font-medium leading-5 text-emerald-700 transition duration-150 ease-in-out hover:bg-emerald-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.25a1.5 1.5 0 0 0-.44-1.06l-3.75-3.75A1.5 1.5 0 0 0 11.75 2H4.5ZM6 10a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $canManageTalent ? 'Editar' : 'Ver' }} CV espanol
                                                </a>
                                            @elseif ($canManageTalent && $talent->cvProfiles->count() < \App\Models\CvProfile::MAX_PER_TALENT)
                                                <form method="POST" action="{{ route('talents.cv.store', $talent) }}">
                                                    @csrf
                                                    <input type="hidden" name="language" value="es">
                                                    <button type="submit" class="flex w-full items-center gap-2 rounded bg-emerald-50 px-3 py-2 text-start text-sm font-medium leading-5 text-emerald-700 transition duration-150 ease-in-out hover:bg-emerald-100" role="menuitem">
                                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                                                        </svg>
                                                        Crear CV en espanol
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($englishCv)
                                                <a href="{{ route('cv.show', $englishCv) }}" class="flex w-full items-center gap-2 rounded bg-teal-50 px-3 py-2 text-start text-sm font-medium leading-5 text-teal-700 transition duration-150 ease-in-out hover:bg-teal-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.25a1.5 1.5 0 0 0-.44-1.06l-3.75-3.75A1.5 1.5 0 0 0 11.75 2H4.5ZM6 10a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $canManageTalent ? 'Editar' : 'Ver' }} CV en ingles
                                                </a>
                                            @elseif ($canManageTalent && $spanishCv && $talent->cvProfiles->count() < \App\Models\CvProfile::MAX_PER_TALENT)
                                                <a href="{{ route('cv.show', $spanishCv) }}" class="flex w-full items-center gap-2 rounded bg-teal-50 px-3 py-2 text-start text-sm font-medium leading-5 text-teal-700 transition duration-150 ease-in-out hover:bg-teal-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                                                    </svg>
                                                    Crear CV en ingles
                                                </a>
                                            @endif
                                            @if ($spanishCv)
                                                <a href="{{ route('cv.download', ['cvProfile' => $spanishCv, 'language' => 'es']) }}" class="flex w-full items-center gap-2 rounded bg-rose-50 px-3 py-2 text-start text-sm font-medium leading-5 text-rose-700 transition duration-150 ease-in-out hover:bg-rose-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                                                        <path d="M3.5 13.75a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 0 .75.75h10a.75.75 0 0 0 .75-.75V14.5a.75.75 0 0 1 1.5 0v1.25A2.25 2.25 0 0 1 15 18H5a2.25 2.25 0 0 1-2.25-2.25V14.5a.75.75 0 0 1 .75-.75Z" />
                                                    </svg>
                                                    Descargar CV espanol PDF
                                                </a>
                                                <a href="{{ route('cv.download-word', ['cvProfile' => $spanishCv, 'language' => 'es']) }}" class="flex w-full items-center gap-2 rounded bg-blue-50 px-3 py-2 text-start text-sm font-medium leading-5 text-blue-700 transition duration-150 ease-in-out hover:bg-blue-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                                                        <path d="M3.5 13.75a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 0 .75.75h10a.75.75 0 0 0 .75-.75V14.5a.75.75 0 0 1 1.5 0v1.25A2.25 2.25 0 0 1 15 18H5a2.25 2.25 0 0 1-2.25-2.25V14.5a.75.75 0 0 1 .75-.75Z" />
                                                    </svg>
                                                    Descargar CV espanol Word
                                                </a>
                                            @endif
                                            @if ($englishCv)
                                                <a href="{{ route('cv.download', ['cvProfile' => $englishCv, 'language' => 'en']) }}" class="flex w-full items-center gap-2 rounded bg-rose-50 px-3 py-2 text-start text-sm font-medium leading-5 text-rose-700 transition duration-150 ease-in-out hover:bg-rose-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                                                        <path d="M3.5 13.75a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 0 .75.75h10a.75.75 0 0 0 .75-.75V14.5a.75.75 0 0 1 1.5 0v1.25A2.25 2.25 0 0 1 15 18H5a2.25 2.25 0 0 1-2.25-2.25V14.5a.75.75 0 0 1 .75-.75Z" />
                                                    </svg>
                                                    Descargar CV ingles PDF
                                                </a>
                                                <a href="{{ route('cv.download-word', ['cvProfile' => $englishCv, 'language' => 'en']) }}" class="flex w-full items-center gap-2 rounded bg-blue-50 px-3 py-2 text-start text-sm font-medium leading-5 text-blue-700 transition duration-150 ease-in-out hover:bg-blue-100" role="menuitem">
                                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                                                        <path d="M3.5 13.75a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 0 .75.75h10a.75.75 0 0 0 .75-.75V14.5a.75.75 0 0 1 1.5 0v1.25A2.25 2.25 0 0 1 15 18H5a2.25 2.25 0 0 1-2.25-2.25V14.5a.75.75 0 0 1 .75-.75Z" />
                                                    </svg>
                                                    Descargar CV ingles Word
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($canManageTalent)
                                        <dialog id="create-application-{{ $talent->id }}" class="w-full max-w-lg rounded-lg p-0 text-left shadow-xl backdrop:bg-gray-500/75">
                                            <form method="POST" action="{{ route('talents.applications.store', $talent) }}" class="space-y-5 p-6">
                                                @csrf
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900">Crear postulacion</h3>
                                                    <p class="mt-1 text-sm text-gray-500">{{ $talent->full_name }}</p>
                                                </div>

                                                <label class="block">
                                                    <span class="text-sm font-medium text-gray-700">Vacante</span>
                                                    <select name="vacancy_id" class="mt-1 w-full rounded border-gray-300 text-sm" @disabled(! $hasAvailableVacancies)>
                                                        <option value="">Selecciona vacante</option>
                                                        @foreach ($vacancies as $vacancy)
                                                            <option value="{{ $vacancy->id }}" @disabled(in_array($vacancy->id, $appliedVacancyIds, true))>
                                                                {{ $vacancy->display_title }} - {{ $vacancy->display_company ?? 'Cliente confidencial' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                @unless ($hasAvailableVacancies)
                                                    <p class="rounded bg-amber-50 px-3 py-2 text-sm text-amber-800">No hay vacantes disponibles para postular a este talento.</p>
                                                @endunless

                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <a href="{{ route('vacancies.create') }}" class="inline-flex justify-center rounded bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Crear nueva vacante</a>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" onclick="document.getElementById('create-application-{{ $talent->id }}').close()" class="rounded bg-gray-100 px-4 py-2 text-sm text-gray-700">Cancelar</button>
                                                        <button class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50" @disabled(! $hasAvailableVacancies)>Crear postulacion</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </dialog>
                                    @endif
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
            const filterForm = document.getElementById('talent-filters');
            const filterInputs = Array.from(document.querySelectorAll('input[form="talent-filters"]'));

            filterInputs.forEach((input) => {
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        filterForm.submit();
                    }
                });
            });

            const refreshBulkState = () => {
                const checkedCheckboxes = checkboxes.filter((checkbox) => checkbox.checked);

                downloadButton.disabled = checkedCheckboxes.length === 0;
                selectedCount.textContent = `${checkedCheckboxes.length} talento${checkedCheckboxes.length === 1 ? '' : 's'} seleccionado${checkedCheckboxes.length === 1 ? '' : 's'}`;
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
