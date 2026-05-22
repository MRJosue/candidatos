<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $company->name }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('companies.edit', $company) }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Editar</a>
                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Eliminar esta compania?')">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 bg-red-50 text-red-700 rounded text-sm">Eliminar</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Notas</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ $company->notes ?: 'Sin notas registradas.' }}</p>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Datos de compania</h3>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-gray-500">Industria</dt><dd>{{ $company->industry ?? 'Sin industria' }}</dd></div>
                        <div><dt class="text-gray-500">Email</dt><dd>{{ $company->email ?? 'Sin email' }}</dd></div>
                        <div><dt class="text-gray-500">Sitio web</dt><dd>{{ $company->website_url ?? 'Sin sitio web' }}</dd></div>
                        <div><dt class="text-gray-500">Ubicacion</dt><dd>{{ $company->location ?? 'Sin ubicacion' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Vacantes</h3>
                    <div class="space-y-3">
                        @forelse ($company->vacancies as $vacancy)
                            <a href="{{ route('vacancies.show', $vacancy) }}" class="flex justify-between border-b pb-3 last:border-b-0">
                                <span>
                                    {{ $vacancy->display_title }}
                                    <span class="block text-sm text-gray-500">{{ $vacancy->location ?? 'Ubicacion por definir' }}</span>
                                </span>
                                <span class="text-sm text-gray-500 capitalize">{{ $vacancy->status }}</span>
                            </a>
                        @empty
                            <p class="text-gray-500">Sin vacantes asociadas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-4">Puestos</h3>
                    <div class="space-y-3">
                        @forelse ($company->positions as $position)
                            <div class="flex justify-between border-b pb-3 last:border-b-0">
                                <span>
                                    {{ $position->title }}
                                    <span class="block text-sm text-gray-500">{{ $position->location ?? 'Ubicacion por definir' }}</span>
                                </span>
                                <span class="text-sm text-gray-500">{{ $position->seniority ?? 'Sin senioridad' }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500">Sin puestos asociados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
