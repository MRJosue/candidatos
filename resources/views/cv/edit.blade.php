<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar CV</h2></x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <form method="POST" action="{{ route('cv.update', $profile) }}" class="space-y-4">
            @method('PUT')
            @include('cv._form')
        </form>
    </div></div>
</x-app-layout>
