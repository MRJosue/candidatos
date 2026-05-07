<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Companias</h2>
            <a href="{{ route('companies.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nueva compania</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded mb-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white rounded shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Industria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicacion</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vacantes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puestos</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($companies as $company)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('companies.show', $company) }}" class="font-medium text-indigo-600">{{ $company->name }}</a>
                                    <p class="text-sm text-gray-500">{{ $company->website_url ?? 'Sin sitio web' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $company->industry ?? 'Sin industria' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $company->location ?? 'Sin ubicacion' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $company->vacancies_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $company->positions_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('companies.edit', $company) }}" class="text-indigo-600">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Aun no tienes companias registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>
