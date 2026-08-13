<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administración</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear usuario</h2>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Volver</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <p class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('name')"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="email" value="Correo" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            class="mt-1 block w-full"
                            :value="old('email')"
                            required
                            autocomplete="username"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Contraseña" />
                        <x-text-input
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                    </div>
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
                                    @checked(collect(old('roles', []))->contains($role->name))
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
                            <option value="{{ $owner->id }}" @selected(old('account_owner_id') == $owner->id)>
                                {{ $owner->name }} - {{ $owner->email }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500">
                        Usalo para usuarios con rol usuario_subordinado. El jefe de cuenta podra ver sus CVs.
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('account_owner_id')" />
                </div>

                <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    El usuario se creara con primer inicio pendiente hasta que actualice su contraseña.
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button>Crear usuario</x-primary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
