<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administración</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Planes de jefes de cuenta</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'usage-subscription-saved')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Plan actualizado.</p>
            @endif

            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Jefe de cuenta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Periodo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $account)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $account->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $account->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $account->cvUsageSubscription?->plan?->name ?? 'Sin plan' }}
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($account->cvUsageSubscription?->plan?->monthly_quota ?? 0) }} CV/mes
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if ($account->cvUsageSubscription)
                                            {{ $account->cvUsageSubscription->current_period_starts_at->format('d/m/Y') }}
                                            -
                                            {{ $account->cvUsageSubscription->current_period_ends_at->format('d/m/Y') }}
                                        @else
                                            Sin periodo
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ ucfirst($account->cvUsageSubscription?->status ?? 'sin estado') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.usage-subscriptions.edit', $account) }}" class="text-amber-700 hover:text-amber-900">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No hay jefes de cuenta registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
