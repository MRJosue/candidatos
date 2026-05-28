<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administración</p>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'user-roles-saved')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Roles actualizados.</p>
            @endif

            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Roles</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Jefe de cuenta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Alta</th>
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
                                        @forelse ($account->roles as $role)
                                            <span class="me-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-gray-500">Sin rol</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $account->accountOwner?->name ?? 'No asignado' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $account->created_at?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.users.edit', $account) }}" class="text-amber-700 hover:text-amber-900">Editar roles</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No hay usuarios registrados.</td>
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
