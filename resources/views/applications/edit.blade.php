<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar postulacion</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
            <form method="POST" action="{{ route('applications.update', $application) }}" class="space-y-6">
                @method('PUT')
                @include('applications.form')
            </form>
        </div>
    </div>
</x-app-layout>
