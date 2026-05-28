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
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900">Nota</h3>
                        <p class="mt-3 text-sm text-gray-600">
                            Este plan aplica al jefe de cuenta y a todos sus usuarios subordinados. Al cambiar la fecha de inicio o corte, el consumo visible se recalcula con los eventos del grupo dentro de ese periodo.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
