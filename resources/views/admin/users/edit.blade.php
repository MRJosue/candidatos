<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administración</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar usuario</h2>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Volver</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'user-roles-saved')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Roles actualizados.</p>
            @endif

            @if ($errors->any())
                <p class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('admin.users.update', $account) }}" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @csrf
                @method('patch')

                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $account->name }}</p>
                    <p class="text-sm text-gray-500">{{ $account->email }}</p>
                </div>

                <div class="mt-6">
                    <x-input-label value="Roles" />
                    <div class="mt-3 space-y-3">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->name }}"
                                    @checked(collect(old('roles', $account->roles->pluck('name')->all()))->contains($role->name))
                                    class="rounded border-gray-300 text-amber-700 shadow-sm focus:ring-amber-500"
                                >
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('roles')" />
                </div>

                <div class="mt-6">
                    <x-input-label for="account_owner_id" value="Jefe de cuenta" />
                    <select id="account_owner_id" name="account_owner_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Sin jefe asignado</option>
                        @foreach ($accountOwners as $owner)
                            <option value="{{ $owner->id }}" @selected(old('account_owner_id', $account->account_owner_id) == $owner->id)>
                                {{ $owner->name }} - {{ $owner->email }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500">
                        Usalo para usuarios con rol usuario_subordinado. El jefe de cuenta podra ver sus CVs.
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('account_owner_id')" />
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button>Guardar roles</x-primary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
