<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Panel de control</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard reclutador</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('talents.create') }}" class="rounded bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800">Nuevo talento</a>
                <a href="{{ route('vacancies.create') }}" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Nueva vacante</a>
            </div>
        </div>
    </x-slot>

    <div class="app-dashboard py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('talents.index') }}" class="rounded bg-white p-4 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-gray-500">Talentos activos</p>
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Cartera</span>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $activeTalentCount }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $talentCount }} talentos registrados</p>
                </a>
                <a href="{{ route('vacancies.index') }}" class="rounded bg-white p-4 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-gray-500">Vacantes abiertas</p>
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Activas</span>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $activeVacancyCount }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $companyCount }} companias · {{ $positionCount }} puestos</p>
                </a>
                <a href="{{ route('applications.index') }}" class="rounded bg-white p-4 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-gray-500">Postulaciones</p>
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Pipeline</span>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $applicationCount }}</p>
                    <p class="mt-1 text-xs text-gray-400">procesos en seguimiento</p>
                </a>
                <a href="{{ route('cv.index') }}" class="rounded bg-white p-4 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-gray-500">CVs generados</p>
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Docs</span>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $cvCount }}</p>
                    <p class="mt-1 text-xs text-gray-400">documentos de candidatos</p>
                </a>
            </div>

            <div class="grid gap-4 xl:grid-cols-12">
                <div class="rounded bg-white shadow-sm ring-1 ring-gray-100 xl:col-span-5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <h3 class="font-semibold text-gray-900">Talento reciente</h3>
                        <a href="{{ route('talents.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver lista</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($recentTalents as $talent)
                            <a href="{{ route('talents.show', $talent) }}" class="block px-4 py-3 hover:bg-gray-50">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900">{{ $talent->full_name }}</p>
                                        <p class="truncate text-sm text-gray-500">{{ $talent->target_position ?? $talent->headline ?? 'Sin objetivo definido' }}</p>
                                    </div>
                                    <span class="shrink-0 rounded bg-gray-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $talent->status }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-sm text-gray-500">Aun no tienes talentos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded bg-white shadow-sm ring-1 ring-gray-100 xl:col-span-3">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h3 class="font-semibold text-gray-900">Pipeline</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($pipelineStages as $stage)
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <x-application-stage-badge :stage="$stage->stage" />
                                <span class="rounded bg-gray-900 px-2.5 py-1 text-sm font-semibold text-white">{{ $stage->total }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-gray-500">Sin postulaciones en seguimiento.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded bg-white shadow-sm ring-1 ring-gray-100 xl:col-span-4">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <h3 class="font-semibold text-gray-900">Proximas citas</h3>
                        <a href="{{ route('appointments.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver agenda</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($nextAppointments as $appointment)
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-800">{{ $appointment->talent?->full_name ?? 'Candidato no disponible' }}</span>
                                    <span class="block truncate text-xs text-gray-500">{{ $appointment->vacancy?->display_title ?? 'Vacante no disponible' }}</span>
                                </span>
                                <span class="shrink-0 text-sm text-gray-500">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-gray-500">Aun no tienes citas programadas.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Vacantes abiertas</h3>
                    <a href="{{ route('vacancies.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver vacantes</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($openVacancies as $vacancy)
                        <a href="{{ route('vacancies.show', $vacancy) }}" class="grid gap-2 px-4 py-3 hover:bg-gray-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-gray-900">{{ $vacancy->display_title }}</span>
                                <span class="block truncate text-sm text-gray-500">{{ $vacancy->display_company ?? 'Cliente confidencial' }}</span>
                            </span>
                            <span class="text-sm font-medium text-gray-500">{{ $vacancy->applications_count }} postulantes</span>
                        </a>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-500">No hay vacantes abiertas.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <a href="{{ route('companies.index') }}" class="rounded border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Companias
                    <span class="ml-2 text-gray-400">{{ $companyCount }}</span>
                </a>
                <a href="{{ route('applications.index') }}" class="rounded border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Postulaciones
                    <span class="ml-2 text-gray-400">{{ $applicationCount }}</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
