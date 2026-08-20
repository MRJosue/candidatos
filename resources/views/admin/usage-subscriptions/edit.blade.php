<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administración</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar plan de cuenta</h2>
            </div>
            <a href="{{ route('admin.usage-subscriptions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Volver</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'usage-subscription-saved')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Plan actualizado.</p>
            @endif
            @if (session('status') === 'usage-subscription-period-reset')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Periodo reiniciado.</p>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <form method="POST" action="{{ route('admin.usage-subscriptions.update', $account) }}" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    @csrf
                    @method('patch')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <p class="text-sm font-medium text-gray-900">{{ $account->name }}</p>
                            <p class="text-sm text-gray-500">{{ $account->email }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="cv_usage_plan_id" value="Plan" />
                            <select id="cv_usage_plan_id" name="cv_usage_plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('cv_usage_plan_id', $subscription->cv_usage_plan_id) == $plan->id)>
                                        {{ $plan->name }} - {{ number_format($plan->monthly_quota) }} CV/mes
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('cv_usage_plan_id')" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="extra_cv_quota" value="CV extra para esta cuenta" />
                            <x-text-input
                                id="extra_cv_quota"
                                name="extra_cv_quota"
                                type="number"
                                min="0"
                                step="1"
                                class="mt-1 block w-full"
                                :value="old('extra_cv_quota', $subscription->extra_cv_quota)"
                                required
                            />
                            <p class="mt-2 text-sm text-gray-500">
                                Se suma al limite mensual del plan solo para esta cuenta. Usa 0 si no tiene CV extra.
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('extra_cv_quota')" />
                        </div>

                        <div>
                            <x-input-label for="current_period_starts_at" value="Fecha de inicio" />
                            <x-text-input
                                id="current_period_starts_at"
                                name="current_period_starts_at"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('current_period_starts_at', $subscription->current_period_starts_at->toDateString())"
                                required
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('current_period_starts_at')" />
                        </div>

                        <div>
                            <x-input-label for="current_period_ends_at" value="Fecha de corte" />
                            <x-text-input
                                id="current_period_ends_at"
                                name="current_period_ends_at"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('current_period_ends_at', $subscription->current_period_ends_at->toDateString())"
                                required
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('current_period_ends_at')" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="status" value="Estado" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                                @foreach (['active' => 'Activa', 'paused' => 'Pausada', 'cancelled' => 'Cancelada'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $subscription->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Guardar plan</x-primary-button>
                        <a href="{{ route('admin.usage-subscriptions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancelar</a>
                    </div>
                </form>

                <aside class="space-y-6">
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">Uso actual</h3>

                        <dl class="mt-5 space-y-4 text-sm">
                            <div>
                                <dt class="text-gray-500">Consumidos</dt>
                                <dd class="font-semibold text-gray-900">{{ number_format($summary['used']) }} / {{ number_format($summary['quota']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Plan base</dt>
                                <dd class="font-semibold text-gray-900">{{ number_format($summary['baseQuota']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">CV extra</dt>
                                <dd class="font-semibold text-gray-900">{{ number_format($summary['extraQuota']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Restantes</dt>
                                <dd class="font-semibold text-gray-900">{{ number_format($summary['remaining']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Periodo</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ $subscription->current_period_starts_at->format('d/m/Y') }}
                                    -
                                    {{ $subscription->current_period_ends_at->format('d/m/Y') }}
                                </dd>
                            </div>
                        </dl>

                        <form
                            method="POST"
                            action="{{ route('admin.usage-subscriptions.reset-period', $account) }}"
                            class="mt-6"
                            onsubmit="return confirm('Esto reiniciara el periodo actual desde este momento. ¿Continuar?')"
                        >
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md border border-amber-600 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-700 transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                Reiniciar periodo
                            </button>
                        </form>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">Nota</h3>
                        <p class="mt-3 text-sm text-gray-600">
                            Este plan aplica al jefe de cuenta y a todos sus usuarios subordinados. Al cambiar la fecha de inicio o corte, el consumo visible se recalcula con los eventos del grupo dentro de ese periodo.
                        </p>
                    </div>
                </aside>
            </div>

            <div class="mt-6 overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="border-b border-gray-100 p-4 sm:px-8 sm:py-6">
                    <h3 class="text-lg font-medium text-gray-900">Consumo por usuario</h3>
                    <p class="mt-1 text-sm text-gray-500">CV usados dentro del periodo actual por el usuario principal y sus subordinados.</p>
                </div>

                @if ($accountUsage->isEmpty())
                    <p class="p-4 text-sm text-gray-500 sm:px-8">Esta cuenta no tiene usuarios para mostrar.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 sm:px-8">Usuario</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Correo</th>
                                    <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-600">CV usados</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 sm:px-8">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($accountUsage as $usageUser)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900 sm:px-8">
                                            <a href="{{ route('admin.users.edit', $usageUser) }}" class="hover:text-amber-700">
                                                {{ $usageUser->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $usageUser->current_period_usage_role }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $usageUser->email }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($usageUser->current_period_usage) }}</td>
                                        <td class="px-4 py-3 text-gray-600 sm:px-8">
                                            <details class="group">
                                                <summary class="cursor-pointer text-sm font-medium text-amber-700 hover:text-amber-900">
                                                    Ver detalle
                                                </summary>

                                                @if ($usageUser->current_period_usage_events->isEmpty())
                                                    <p class="mt-2 text-sm text-gray-500">Sin consumo en este periodo.</p>
                                                @else
                                                    <div class="mt-2 overflow-hidden rounded-md border border-gray-200">
                                                        <table class="min-w-full divide-y divide-gray-100 text-xs">
                                                            <thead class="bg-gray-50">
                                                                <tr>
                                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-600">Fecha</th>
                                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-600">Consumo</th>
                                                                    <th scope="col" class="px-3 py-2 text-left font-semibold text-gray-600">CV</th>
                                                                    <th scope="col" class="px-3 py-2 text-right font-semibold text-gray-600">Cantidad</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                                @foreach ($usageUser->current_period_usage_events as $event)
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-gray-500">{{ $event->occurred_at->format('d/m/Y H:i') }}</td>
                                                                        <td class="px-3 py-2 text-gray-700">{{ $event->type_label }}</td>
                                                                        <td class="px-3 py-2 text-gray-500">
                                                                            {{ $event->cvProfile?->title ?? data_get($event->metadata, 'original_name', 'No disponible') }}
                                                                        </td>
                                                                        <td class="px-3 py-2 text-right font-semibold text-gray-900">{{ number_format($event->quantity) }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
