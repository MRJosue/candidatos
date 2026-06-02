<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Habilidades</h2></x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        @foreach ($skills as $skill)
            <span class="inline-block px-2 py-1 bg-gray-100 rounded m-1">
                {{ $skill->name }}
                <span class="text-xs text-gray-500">
                    {{ ['software' => 'Software', 'skill' => 'Habilidad', 'language' => 'Idioma', 'certification' => 'Certificacion', 'soft_skill' => 'Habilidad blanda'][$skill->type ?? 'skill'] ?? 'Habilidad' }}
                </span>
            </span>
        @endforeach
        {{ $skills->links() }}
    </div></div>
</x-app-layout>
