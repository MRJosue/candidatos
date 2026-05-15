<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Educación</h2>
            <a href="{{ route('cv.education.create', $profile) }}" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">Agregar educacion</a>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        @forelse ($education as $item)
            <div class="flex items-center justify-between gap-4 py-3 border-b">
                <p>{{ $item->degree }} · {{ $item->institution }}</p>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('education.edit', $item) }}" class="text-indigo-700">Editar</a>
                    <form method="POST" action="{{ route('education.destroy', $item) }}" onsubmit="return confirm('¿Eliminar esta educacion?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-700">Eliminar</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Aun no has agregado educacion.</p>
        @endforelse
        {{ $education->links() }}
    </div></div>
</x-app-layout>
