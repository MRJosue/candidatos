<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Consumo</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Uso mensual de CVs</h2>
        </div>
    </x-slot>

    <div class="app-dashboard py-5">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Plan actual</h3>
                </div>

                <div class="grid gap-4 px-5 py-5 sm:grid-cols-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Cuenta</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $summary['accountOwner']->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Plan</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $summary['plan']->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Usados</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($summary['used']) }} / {{ number_format($summary['quota']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Restantes</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($summary['remaining']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Reinicio</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $usageService->formatPeriodDate($summary['subscription']->current_period_ends_at) }}</p>
                    </div>
                </div>

                <div class="px-5 pb-5">
                    <div class="h-2 overflow-hidden rounded bg-gray-100">
                        <div class="h-full rounded bg-gray-900" style="width: {{ $summary['percentage'] }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Los CVs incluidos son compartidos por todos los usuarios de la cuenta, se renuevan cada mes y no son acumulables.
                    </p>
                </div>
            </div>

            <div class="rounded bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-900">Historial del periodo</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-gray-900">
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Usuario</th>
                                <th class="px-4 py-3 font-semibold">Uso</th>
                                <th class="px-4 py-3 font-semibold">CV</th>
                                <th class="px-4 py-3 font-semibold">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($events as $event)
                                <tr>
                                    <td class="px-4 py-3">{{ $event->occurred_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">{{ $event->user?->name ?? 'Usuario' }}</td>
                                    <td class="px-4 py-3">{{ $usageService->labelForEventType($event->type) }}</td>
                                    <td class="px-4 py-3">
                                        {{ $event->cvProfile?->title ?? $event->cvProfile?->full_name ?? 'Sin CV asociado' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $event->quantity }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Todavia no hay consumo registrado en este periodo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($events->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
