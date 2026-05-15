<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar tema</h2>

            @unless ($theme->is_default)
                <form method="POST" action="{{ route('admin.themes.destroy', $theme) }}" onsubmit="return confirm('¿Eliminar este tema?')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar</button>
                </form>
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'theme-saved')
                <p class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Tema guardado.</p>
            @endif

            <form method="POST" action="{{ route('admin.themes.update', $theme) }}" enctype="multipart/form-data">
                @method('patch')
                @include('admin.themes._form')
            </form>
        </div>
    </div>
</x-app-layout>
