<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar cita</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('appointments.form', ['appointment' => $appointment])
        </form>
    </div></div>
</x-app-layout>
