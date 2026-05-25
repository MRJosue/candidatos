<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis CVs</h2>
            <a href="{{ route('cv.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded">Nuevo CV</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            @forelse ($profiles as $profile)
                <div class="bg-white p-5 rounded shadow-sm flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold">{{ $profile->title }}</h3>
                        <p class="text-sm text-gray-500">{{ $profile->full_name }} · {{ $profile->template?->name ?? 'Sin plantilla' }} · {{ $profile->languageLabel() }}</p>
                        <p class="text-sm text-gray-500">
                            Postulante: {{ $profile->talent?->full_name ?? 'Sin asignar' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('cv.show', $profile) }}" class="text-indigo-700">Abrir</a>
                        <button
                            type="button"
                            onclick="document.getElementById('assign-cv-{{ $profile->id }}').showModal()"
                            class="text-gray-700 hover:text-gray-900"
                        >
                            Asignar postulante
                        </button>
                        <form method="POST" action="{{ route('cv.destroy', $profile) }}" onsubmit="return confirm('¿Eliminar este CV? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                        </form>
                    </div>
                </div>

                <dialog id="assign-cv-{{ $profile->id }}" class="w-full max-w-lg rounded-lg p-0 shadow-xl backdrop:bg-gray-500/75">
                    <form method="POST" action="{{ route('cv.talent.update', $profile) }}" class="p-6 space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Asignar postulante</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $profile->title }}</p>
                        </div>

                        <label class="block">
                            <span class="text-sm text-gray-700">Postulante</span>
                            <select name="talent_id" class="mt-1 w-full rounded border-gray-300">
                                <option value="">Sin asignar</option>
                                @foreach ($talents as $talent)
                                    <option
                                        value="{{ $talent->id }}"
                                        @selected($profile->talent_id === $talent->id)
                                    >
                                        {{ $talent->full_name }}
                                        @if ($talent->cvProfiles->isNotEmpty())
                                            - {{ $talent->cvProfiles->count() }} CV(s)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="document.getElementById('assign-cv-{{ $profile->id }}').close()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</button>
                            <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
                        </div>
                    </form>
                </dialog>
            @empty
                <div class="bg-white p-6 rounded shadow-sm text-gray-600">Crea tu primer CV para empezar.</div>
            @endforelse

            {{ $profiles->links() }}
        </div>
    </div>
</x-app-layout>
