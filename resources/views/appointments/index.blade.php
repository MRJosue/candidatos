<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Citas</h2>
            <a href="{{ route('appointments.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nueva cita</a>
        </div>
    </x-slot>

    @php
        $activeTab = request('tab', 'list');
        $previousMonth = $calendarMonth->subMonth()->format('Y-m');
        $nextMonth = $calendarMonth->addMonth()->format('Y-m');
        $today = now()->toDateString();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6" aria-label="Tabs">
                    <a href="{{ route('appointments.index', ['tab' => 'list', 'month' => $calendarMonth->format('Y-m')]) }}"
                        class="border-b-2 px-1 py-3 text-sm font-medium {{ $activeTab === 'list' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        Listado
                    </a>
                    <a href="{{ route('appointments.index', ['tab' => 'calendar', 'month' => $calendarMonth->format('Y-m')]) }}"
                        class="border-b-2 px-1 py-3 text-sm font-medium {{ $activeTab === 'calendar' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        Calendario
                    </a>
                </nav>
            </div>

            @if ($activeTab === 'calendar')
                <div class="bg-white rounded shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">Calendario de citas</p>
                            <h3 class="text-2xl font-semibold text-gray-900">{{ $calendarMonth->locale('es')->translatedFormat('F Y') }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('appointments.index', ['tab' => 'calendar', 'month' => $previousMonth]) }}" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700">Anterior</a>
                            <a href="{{ route('appointments.index', ['tab' => 'calendar']) }}" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700">Hoy</a>
                            <a href="{{ route('appointments.index', ['tab' => 'calendar', 'month' => $nextMonth]) }}" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700">Siguiente</a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[56rem]">
                            <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-medium uppercase text-gray-500">
                                @foreach (['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'] as $weekday)
                                    <div class="px-3 py-3">{{ $weekday }}</div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-1 divide-y divide-gray-200">
                                @foreach ($calendarWeeks as $week)
                                    <div class="grid grid-cols-7 divide-x divide-gray-200">
                                        @foreach ($week as $day)
                                            @php
                                                $dayAppointments = $calendarAppointmentsByDate->get($day->toDateString(), collect());
                                                $isCurrentMonth = $day->isSameMonth($calendarMonth);
                                                $isToday = $day->toDateString() === $today;
                                            @endphp
                                            <div class="min-h-36 p-3 {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50 text-gray-400' }}">
                                                <div class="flex items-center justify-between">
                                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium {{ $isToday ? 'bg-gray-900 text-white' : 'text-gray-700' }}">
                                                        {{ $day->day }}
                                                    </span>
                                                    @if ($dayAppointments->isNotEmpty())
                                                        <span class="text-xs text-gray-500">{{ $dayAppointments->count() }}</span>
                                                    @endif
                                                </div>

                                                <div class="mt-3 space-y-2">
                                                    @foreach ($dayAppointments as $appointment)
                                                        <a href="{{ route('appointments.show', $appointment) }}" class="block rounded border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-900 hover:border-indigo-200">
                                                            <span class="block font-semibold">{{ $appointment->scheduled_at->format('H:i') }} · {{ $appointment->talent?->full_name ?? 'Candidato no disponible' }}</span>
                                                            <span class="block truncate text-indigo-700">{{ $appointment->vacancy?->display_title ?? 'Vacante no disponible' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @forelse ($appointments as $appointment)
                        <a href="{{ route('appointments.show', $appointment) }}" class="block bg-white p-5 rounded shadow-sm">
                            {{ $appointment->talent?->full_name ?? 'Candidato no disponible' }}
                            · {{ $appointment->vacancy?->display_title ?? 'Vacante no disponible' }}
                            · {{ $appointment->scheduled_at->format('d/m/Y H:i') }}
                            · {{ $appointment->status }}
                        </a>
                    @empty
                        <div class="bg-white p-8 text-center text-gray-500 rounded shadow-sm">Aun no tienes citas registradas.</div>
                    @endforelse
                </div>

                {{ $appointments->appends(request()->query())->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
